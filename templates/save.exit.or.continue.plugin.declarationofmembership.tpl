<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
 
    {$l10n->get('PLG_DECLARATION_OF_MEMBERSHIP_SAVED')}
    <br><br>
 
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_exit']}<br>
    {$l10n->get('PLG_DECLARATION_OF_MEMBERSHIP_EXIT_DESC')}
    <br><br>

    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_continue']}<br>
    {$l10n->get('PLG_DECLARATION_OF_MEMBERSHIP_CONTINUE_DESC')}
    <br><br>

    <div class="form-alert" style="display: none;">&nbsp;</div>
   
</form>
