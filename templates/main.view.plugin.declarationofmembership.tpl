<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
   
    <a class="admidio-icon-link float-end openPopup" href="javascript:void(0);" data-class="modal-lg" data-href="{$urlPopup}">
        <i class="bi bi-info-circle-fill admidio-info-icon"></i>
    </a> 
     
    <div class="admidio-form-required-notice"><span>{$l10n->get('SYS_REQUIRED_INPUT')}</span></div>

    {if isset($pPreferences->config.main_texts.main_pretext)}
        {$pPreferences->create_html($pPreferences->config.main_texts.main_pretext)}  
    {/if}
       
    {$lastCategory = ''}
                                       
    {foreach $elements as $key => $profileField}

        {if {array_key_exists array=$profileField key="category"}}
            {if $profileField.category != $lastCategory}
                {if $lastCategory != ''}
                
                    {if isset($pPreferences->config.cat_texts["{$lastCatId}_posttext"]) && $pPreferences->config.cat_texts["{$lastCatId}_posttext"]|count_characters > 0}
                        {$pPreferences->create_html($pPreferences->config.cat_texts["{$lastCatId}_posttext"])}
                    {/if}
                    </div></div>
                {/if}
                
                {$lastCategory = {$profileField.category}}
                    
                <div class="card admidio-field-group">
                    <div class="card-header">{$profileField.category}</div>
                    <div class="card-body">
                                  
                        {if isset($pPreferences->config.cat_texts["{$profileField.catid}_pretext"]) && $pPreferences->config.cat_texts["{$profileField.catid}_pretext"]|count_characters > 0}
                            {$pPreferences->create_html($pPreferences->config.cat_texts["{$profileField.catid}_pretext"])}
                            <br><br>
                        {/if}
                        {$lastCatId = {$profileField.catid}}
            {/if}

            {if {array_key_exists array=$elements key='usr_login_name'} && $key == 'usr_login_name'}
                {include 'sys-template-parts/form.input.tpl' data=$profileField}
                {if isset($pPreferences->config.usr_login_name.fieldtext)}
                    <div class="offset-3">
                        {$pPreferences->create_html($pPreferences->config.usr_login_name.fieldtext)} 
                    </div>
                {/if}
                <hr />
            {else}
                {if $profileField.type == 'checkbox'}
                    {include '../templates/form.checkbox.popover.plugin.declarationofmembership.tpl' data=$profileField popover=$profileField.popover}
                {elseif $profileField.type == 'multiline'}
                    {include '../templates/form.multiline.popover.plugin.declarationofmembership.tpl' data=$profileField popover=$profileField.popover}
                {elseif $profileField.type == 'radio'}
                    {include '../templates/form.radio.popover.plugin.declarationofmembership.tpl' data=$profileField popover=$profileField.popover}
                {elseif $profileField.type == 'select'}
                    {include '../templates/form.select.popover.plugin.declarationofmembership.tpl' data=$profileField popover=$profileField.popover}
                {else}
                    {include '../templates/form.input.popover.plugin.declarationofmembership.tpl' data=$profileField popover=$profileField.popover}
                {/if}           

                {if isset($pPreferences->config.field_texts["{$profileField.usfid}_fieldtext"]) && $pPreferences->config.field_texts["{$profileField.usfid}_fieldtext"]|count_characters > 0}
                    <div class="offset-3">
                        {$pPreferences->create_html($pPreferences->config.field_texts["{$profileField.usfid}_fieldtext"])}<br><br>
                    </div>
                {/if}    
            {/if}
        {/if}
        
    {/foreach}
    
    {if isset($pPreferences->config.cat_texts["{$lastCatId}_posttext"]) && $pPreferences->config.cat_texts["{$lastCatId}_posttext"]|count_characters > 0}
        {$pPreferences->create_html($pPreferences->config.cat_texts["{$lastCatId}_posttext"])}
    {/if}
   
                    </div>
                </div>
      
    {if {array_key_exists array=$elements key='adm_captcha_code'}}
        <div class="card admidio-field-group">
            <div class="card-header">{$l10n->get('SYS_CONFIRMATION_OF_INPUT')}</div>
            <div class="card-body">
                {include 'sys-template-parts/form.captcha.tpl' data=$elements['adm_captcha_code']}
            </div>
        </div>
    {/if}
    
    {if isset($pPreferences->config.main_texts.main_posttext)}
        {$pPreferences->create_html($pPreferences->config.main_texts.main_posttext)}  
    {/if}
        
    <div class="form-alert" style="display: none;">&nbsp;</div>
    {if {array_key_exists array=$elements key='adm_button_save'}}
        <br><br>
        {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save']}
    {/if}
</form>
