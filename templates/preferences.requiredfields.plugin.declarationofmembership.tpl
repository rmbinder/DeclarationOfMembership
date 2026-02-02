<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
   
    {$lastCategory = ''}
           
    {foreach $elements as $key => $profileField}
     
        {if {array_key_exists array=$profileField key="category"}}
            {if $profileField.category != $lastCategory}
                {if $lastCategory != ''}   
                    {include 'sys-template-parts/form.multiline.tpl' data=$elements["{$lastCatId}_posttext"]}
                    </div></div>
                {/if}
                
                {$lastCategory = {$profileField.category}}
                    
                <div class="card admidio-field-group">
                    <hr />
                    <div class="card-header">{$profileField.category}</div>
                    <div class="card-body">
            {/if}

            {if {array_key_exists array=$elements key="{$profileField.catid}_pretext"} && $key == "{$profileField.catid}_pretext"}       
                {include 'sys-template-parts/form.multiline.tpl' data=$profileField}
                {$lastCatId = {$profileField.catid}}
            {/if} 
            
            {if (($key != "{$profileField.catid}_pretext") && ($key != "{$profileField.catid}_posttext"))}
                {if $profileField.type == 'checkbox'}
                    {include 'sys-template-parts/form.checkbox.tpl' data=$profileField}
                {elseif $profileField.type == 'multiline'}
                    {include 'sys-template-parts/form.multiline.tpl' data=$profileField}
                {/if}     
             {/if} 
        {/if}
        
    {/foreach}
      
    {include 'sys-template-parts/form.multiline.tpl' data=$elements["{$lastCatId}_posttext"]}
                
                    </div>
                </div>
    <hr />
        
    <div class="form-alert" style="display: none;">&nbsp;</div>
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save']}
    
</form>
