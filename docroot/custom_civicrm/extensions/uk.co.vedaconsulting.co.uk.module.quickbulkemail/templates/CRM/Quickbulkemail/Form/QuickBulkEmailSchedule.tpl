<div class="crm-block crm-form-block crm-quickbulkemail-step-2">
{* HEADER *}

<!--<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>-->
{literal}
<style>
  fieldset legend {
     float: none;
  }
  .crm-section{
    padding-left:5px;
  }
  .crm-container .icon{
    width: 0px;
  }
  </style>
 {/literal}

{* FIELD EXAMPLE: OPTION 2 (MANUAL LAYOUT) *}
  <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
	<div class="crm-accordion-header">
	<div class="icon crm-accordion-pointer"></div> 
		{ts}Test Mailing{/ts}
	</div><!-- /.crm-accordion-header -->
	<div class="crm-accordion-body">
      <div class="crm-section">
		<div class="label">{$form.test_email.label}</div>
		<div class="content">{$form.test_email.html}&nbsp;{ts}(filled with your contact's token values){/ts}</div>
		<div class="clear"></div>
	  </div>
      <div class="crm-section">
		<div class="label">{$form.test_group.label}</div>
		<div class="content">{$form.test_group.html}</div>
		<div class="clear"></div>
	  </div>
      <div class="crm-section">
		<div class="label"></div>
		<div class="content">{$form.sendtest.html}</div>
		<div class="clear"></div>
	  </div>  
	</div>
  </div>
  <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
	<div class="crm-accordion-header">
	<div class="icon crm-accordion-pointer"></div> 
		{ts}Schedule or Send{/ts}
	</div><!-- /.crm-accordion-header -->
	<div class="crm-accordion-body">
      <div class="crm-section">
		<div class="label">{$form.now.label}</div>
		<div class="content">{$form.now.html}</div>
		<div class="clear"></div>
	  </div>
      <div class="crm-section">
		<div class="label">{ts}OR{/ts}</div>
		<div class="content"></div>
		<div class="clear"></div>
	  </div>  
      <div class="crm-section">
		<div class="label">{$form.start_date.label}</div>
		<div class="content">
            {include file="CRM/common/jcalendar.tpl" elementName=start_date}&nbsp;
            <div class="description">{ts}Set a date and time when you want CiviMail to start sending this mailing.{/ts}</div>
        </div>
		<div class="clear"></div>
	  </div>  
	</div>
   </div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div>