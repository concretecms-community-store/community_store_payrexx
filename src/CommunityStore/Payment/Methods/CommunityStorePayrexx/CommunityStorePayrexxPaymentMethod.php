<?php

namespace Concrete\Package\CommunityStorePayrexx\Src\CommunityStore\Payment\Methods\CommunityStorePayrexx;

use Core;
use URL;
use Config;
use Session;
use Log;

use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus as StoreOrderStatus;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;

class CommunityStorePayrexxPaymentMethod extends StorePaymentMethod
{
    public function dashboardForm()
    {
        $this->set('payrexxInstanceName', Config::get('community_store_payrexx.instanceName'));
        $this->set('payrexxSecret', Config::get('community_store_payrexx.secret'));
        $this->set('payrexxCurrency', Config::get('community_store_payrexx.currency'));
        $currencies = [
            'EUR' => "Euro",
            'CHF' => "Swiss Franc"
        ];
        $this->set('currencies', $currencies);
        $this->set('form', Core::make("helper/form"));
    }

    public function save(array $data = [])
    {
        Config::save('community_store_payrexx.instanceName', $data['payrexxInstanceName']);
        Config::save('community_store_payrexx.secret', $data['payrexxSecret']);
        Config::save('community_store_payrexx.currency', $data['payrexxCurrency']);
    }

    public function validate($args, $e)
    {
        $pm = StorePaymentMethod::getByHandle('community_store_payrexx');
        if ($args['paymentMethodEnabled'][$pm->getID()] == 1) {
            if ($args['payrexxInstanceName'] == "") {
                $e->add(t("Instance Name must be set"));
            }

            if ($args['payrexxSecret'] == "") {
                $e->add(t("Secret must be set"));
            }
        }
        return $e;
    }

    public function redirectForm()
    {
        $order = StoreOrder::getByID(Session::get('orderID'));
        $gateway = $this->getGatewayURL($order);

        if ($gateway && $order) {

            $order->setTransactionReference($gateway['reference_id']);
            $order->save();

            $this->redirect($gateway['link']);
        } else {
            $this->redirect('/checkout/failed#payment');
        }
    }

    public static function validateCompletion()
    {
        // if we get to this function, we returned from payrexx after a payment
        self::redirect('/checkout/complete');

    }

    public function checkoutForm()
    {
        $pmID = StorePaymentMethod::getByHandle('community_store_payrexx')->getID();
        $this->set('pmID', $pmID);
    }

    private static function getGatewayURL($order)
    {
        $instanceName = Config::get('community_store_payrexx.instanceName');
        $secret = Config::get('community_store_payrexx.secret');
        $currency = Config::get('community_store_payrexx.currency');

        $payrexx = new \Payrexx\Payrexx($instanceName, $secret);

        $gateway = new \Payrexx\Models\Request\Gateway();

        // amount multiplied by 100
        $gateway->setAmount(number_format($order->getTotal(), 2, '.', '') * 100);

        // currency ISO code
        $gateway->setCurrency($currency);

        $success = \URL::to('/checkout/payrexxresponse/') . '';
        $failure = \URL::to('/checkout/') . '';

        //success and failed url in case that merchant redirects to payment site instead of using the modal view
        $gateway->setSuccessRedirectUrl($success);
        $gateway->setFailedRedirectUrl($failure);


        // empty array = all available psps
        $gateway->setPsp([]);

        $gateway->setReferenceId( uniqid('trans_'));

        $customer = new StoreCustomer();

        $address = $customer->getValue("billing_address")->address1;

        if ($customer->getValue("billing_address")->address2) {
            $address .= ', '. $customer->getValue("billing_address")->address2;
        }

        $gateway->addField($type = 'title', $value = 'mister');
        $gateway->addField($type = 'forename', $customer->getValue("billing_first_name"));
        $gateway->addField($type = 'surname', $customer->getValue("billing_last_name"));
        $gateway->addField($type = 'company', '');
        $gateway->addField($type = 'street', $address);
        $gateway->addField($type = 'postcode', $customer->getValue("billing_address")->postal_code);
        $gateway->addField($type = 'place', $customer->getValue("billing_address")->city);
        $gateway->addField($type = 'country', $customer->getValue("billing_address")->country);
        $gateway->addField($type = 'phone', $customer->getValue("billing_phone"));
        $gateway->addField($type = 'email', $customer->getEmail());

        $response = $payrexx->create($gateway);

        if ($response->getHash()) {
            return ['hash' => $response->getHash(), 'reference_id' => $response->getReferenceId(), 'link' => $response->getLink()];
        }

        return false;
    }


    public static function callback()
    {

        $transaction = !empty($_POST['transaction']) ? $_POST['transaction'] : array();

        if (!empty($transaction)) {

            $status = $transaction['status'];
            $refid = $transaction['invoice']['referenceId'];

            $em = \ORM::entityManager();
            $order = $em->getRepository('Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order')->findOneBy(array('transactionReference' => $refid));

            if ($order) {
                if ($status == 'confirmed' ) {
                    $order->completePayment(false); // set false as it's not part of the same request as the order
                    $order->save();

                    if ($order->getExternalPaymentRequested()) {
                        $order->completeOrder(null);
                        $order->updateStatus(StoreOrderStatus::getStartingStatus()->getHandle());
                    }
                }

                if ($status == 'cancelled' || $status == 'failed' || $status == 'expired' || $status == 'rejected' || $status == 'waiting' ) {
                    $order->setPaid(null);
                    $order->save();
                }

                if ($status == 'refunded') {
                    $order->setRefunded(new \DateTime());
                    $order->save();
                }
            }

        }


        exit();

    }


    public function getPaymentMinimum()
    {
        return 1.0;
    }

    public function getName()
    {
        return 'Payrexx';
    }

    public function isExternal()
    {
        return true;
    }

    public function markPaid()
    {
        return false;
    }




}
