<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {include 'sys-template-parts/form.multiline.tpl' data=$elements['main_pretext']}
               
    {$lastCategory = ''}
                                       
    {foreach $elements as $key => $profileField}

        {if {array_key_exists array=$profileField key="category"}}
            {if $profileField.category != $lastCategory}
                {if $lastCategory != ''}
                    </div></div>
                {/if}
                
                {$lastCategory = {$profileField.category}}
                    
                <div class="card admidio-field-group">
                    <hr />
                    <div class="card-header">{$profileField.category}</div>
                    <div class="card-body">
                           {$profileField.notetext} 
                           <br><br>
                        {if $profileField.category == $l10n->get("SYS_BASIC_DATA")}
                            {include 'sys-template-parts/form.checkbox.tpl' data=$elements['usr_login_name']}
                            <div class="offset-3"><hr /></div>
                        {/if}      
            {/if}

            {include 'sys-template-parts/form.checkbox.tpl' data=$profileField}
        {/if}
        
    {/foreach}
                    </div>
                </div>
      
    <hr />
    {include 'sys-template-parts/form.multiline.tpl' data=$elements['main_posttext']}
    <hr />
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save']}
    
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
