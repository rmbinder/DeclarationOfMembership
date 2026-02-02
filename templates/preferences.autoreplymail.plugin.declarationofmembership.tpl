<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['autoreplymail_module_enabled']}
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['autoreplymail_link']}
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_autoreply']} 
    
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
