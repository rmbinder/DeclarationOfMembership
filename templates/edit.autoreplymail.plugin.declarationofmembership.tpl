<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
 
    {$l10n->get('PLG_DECLARATION_OF_MEMBERSHIP_AUTOREPLYMAIL_DESC')}
    <br><br>
    {$l10n->get('PLG_DECLARATION_OF_MEMBERSHIP_AUTOREPLYMAIL_INFO')}
    <br>
    
    {include 'sys-template-parts/form.input.tpl' data=$elements['msg_subject']}
    {include 'sys-template-parts/form.editor.tpl' data=$elements['msg_body']}

    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save']}
    <div class="form-alert" style="display: none;">&nbsp;</div>
   
</form>
