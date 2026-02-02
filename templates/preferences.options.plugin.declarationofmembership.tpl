<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {include 'sys-template-parts/form.select.tpl' data=$elements['org_id']}
    {include 'sys-template-parts/form.radio.tpl' data=$elements['kiosk_mode']}
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_options']}
     
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
