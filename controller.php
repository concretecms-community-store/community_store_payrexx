<?php

namespace Concrete\Package\CommunityStorePayrexx;

use Package;
use Route;
use Whoops\Exception\ErrorException;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;

class Controller extends Package
{
    protected $pkgHandle = 'community_store_payrexx';
    protected $appVersionRequired = '5.7.2';
    protected $pkgVersion = '1.0';

    public function getPackageDescription()
    {
        return t("Payrexx Payment Method for Community Store");
    }

    public function getPackageName()
    {
        return t("Payrexx Payment Method");
    }

    public function install()
    {
        $installed = Package::getInstalledHandles();
        if(!(is_array($installed) && in_array('community_store',$installed)) ) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        } else {
            $pkg = parent::install();
            $pm = new PaymentMethod();
            $pm->add('community_store_payrexx','Payrexx',$pkg);
        }

    }
    public function uninstall()
    {
        $pm = PaymentMethod::getByHandle('community_store_payrexx');
        if ($pm) {
            $pm->delete();
        }
        $pkg = parent::uninstall();
    }

    public function on_start() {
        require __DIR__ . '/vendor/autoload.php';
        Route::register('/checkout/payrexxresponse','\Concrete\Package\CommunityStorePayrexx\Src\CommunityStore\Payment\Methods\CommunityStorePayrexx\CommunityStorePayrexxPaymentMethod::validateCompletion');
        Route::register('/payrexxcallback','\Concrete\Package\CommunityStorePayrexx\Src\CommunityStore\Payment\Methods\CommunityStorePayrexx\CommunityStorePayrexxPaymentMethod::callback');
    }

}
?>