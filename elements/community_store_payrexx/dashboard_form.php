<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
?>

<div class="form-group">
    <label><?= t("Payrexx Instance Name")?></label>
    <div class="input-group">
    <input type="text" name="payrexxInstanceName" value="<?= $payrexxInstanceName?>" class="form-control">
        <div class="input-group-addon">.payrexx.com</div>
    </div>
</div>

<div class="form-group">
    <label><?= t("Secret")?></label>
    <input type="text" name="payrexxSecret" value="<?= $payrexxSecret?>" class="form-control">
</div>

<div class="form-group">
    <?= $form->label('payrexxCurrency',t("Currency")); ?>
    <?= $form->select('payrexxCurrency',$currencies,$payrexxCurrency?$payrexxCurrency:'CHF');?>
</div>

<p class="alert alert-info"><strong><?= t('Important');?></strong>: <?= t('Add the following URL as a Webhook within the Payrexx Dashboard, as Normal (PHP-Post) Content-Type'); ?><br /><a href="<?php echo \URL::to('/payrexxcallback'); ?>"><?php echo \URL::to('/payrexxcallback'); ?></a></p>