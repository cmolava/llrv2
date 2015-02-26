<div class="crm-block crm-form-block crm-mailchimp-setting-form-block">
{* HEADER *}

<!--<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>-->

{* FIELDS (MANUAL LAYOUT) *}

{include file="CRM/common/WizardHeader.tpl"}

  <div class="crm-section">
    <div class="label">{$form.name.label}</div>
    <div class="content">{$form.name.html}</div>
    <div class="clear"></div>
  </div>

  <!--
  <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
	<div class="crm-accordion-header">
	<div class="icon crm-accordion-pointer"></div>
		{ts}Basic Send{/ts}
	</div> -->
    <!-- /.crm-accordion-header -->
	<!--<div class="crm-accordion-body">-->
	  <div class="crm-section">
      <div class="label">{$form.includeGroups.label}</div>
      <div class="content">{$form.includeGroups.html}</div>
      <div class="clear"></div>
	  </div>
<!--	</div>
   </div> -->

 <!-- <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
	<div class="crm-accordion-header">
	<div class="icon crm-accordion-pointer"></div>
		{ts}Advanced{/ts}
	</div> --><!-- /.crm-accordion-header -->
	<!--<div class="crm-accordion-body"> -->
	  <div class="crm-section" id="crm-mailing-settings-form-block-campaign">
      {include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignTrClass="crm-mailing-group-form-block-campaign_id"}
      <div class="clear"></div>
	  </div>
    <div class="crm-section">
      <div class="label">{$form.template.label}</div>
      <div class="content">{$form.template.html}</div>
      <div class="clear"></div>
    </div>
    <div class="crm-section">
      <div class="label">{$form.subject.label}</div>
      <div class="content">{$form.subject.html}</div>
      <div class="clear"></div>
	  </div>
      <!--<div class="crm-section">
		<div class="label">{$form.html_message.label}</div>
		<div class="content">{$form.html_message.html}</div>
		<div class="clear"></div>
	  </div>-->
	 <!-- </div> -->
  <!-- </div> -->
   {include file="CRM/Contact/Form/Task/EmailCommon.tpl"}

   <!--<div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
	<div class="crm-accordion-header">
	<div class="icon crm-accordion-pointer"></div>
		{ts}Text Version{/ts}
	</div>
	<div class="crm-accordion-body">
      <div class="crm-section">
		<div class="label">{$form.text_message.label}</div>
		<div class="content">{$form.text_message.html}</div>
		<div class="clear"></div>
	  </div>
	</div>
   </div>-->

   <div class="crm-accordion-wrapper crm-accordion_title-accordion collapsed">
	<div class="crm-accordion-header">
	<div class="icon crm-accordion-pointer"></div>
		{ts}Advanced Options{/ts}
	</div><!-- /.crm-accordion-header -->
	<div class="crm-accordion-body">
      <fieldset>
      <legend>{ts}Header and Footer{/ts}</legend>
      <div class="crm-section">
		<div class="label">{$form.header_id.label}</div>
		<div class="content">{$form.header_id.html}</div>
		<div class="clear"></div>
	  </div>
      <div class="crm-section">
		<div class="label">{$form.footer_id.label}</div>
		<div class="content">{$form.footer_id.html}</div>
		<div class="clear"></div>
	  </div>
      </fieldset>
      <fieldset>
      <legend>{ts}Tracking{/ts}</legend>
      <div class="crm-section">
		<div class="label">{$form.url_tracking.label}</div>
		<div class="content">{$form.url_tracking.html}&nbsp;<span class="description">{ts}Track the number of times recipients click each link in this mailing. NOTE: When this feature is enabled, all links in the message body will be automaticallly re-written to route through your CiviCRM server prior to redirecting to the target page.{/ts}</span></div>
		<div class="clear"></div>
	  </div>
      <div class="crm-section">
		<div class="label">{$form.open_tracking.label}</div>
		<div class="content">{$form.open_tracking.html}</div>
		<div class="clear"></div>
	  </div>
      </fieldset>
      <fieldset>
      <legend>{ts}Responding{/ts}</legend>
      <div class="crm-section">
		<div class="label">{$form.override_verp.label}</div>
		<div class="content">{$form.override_verp.html}</div>
		<div class="clear"></div>
	  </div>
      <div class="crm-section" id="crm-mailing-settings-form-block-forward_replies">
		<div class="label">{$form.forward_replies.label}</div>
		<div class="content">{$form.forward_replies.html}&nbsp;<span class="description">{ts}If a recipient replies to this mailing, forward the reply to the FROM Email address specified for the mailing.{/ts}</span></div>
		<div class="clear"></div>
	  </div>
      <div class="crm-section" id="crm-mailing-settings-form-block-auto_responder">
		<div class="label">{$form.auto_responder.label}</div>
		<div class="content">{$form.auto_responder.html}&nbsp; {$form.reply_id.html}&nbsp;<span class="description">{ts}If a recipient replies to this mailing, send an automated reply using the selected message.{/ts}</span></div>
		<div class="clear"></div>
	  </div>
      </fieldset>
      <fieldset>
      <legend>{ts}From Email Address and Reply-to{/ts}</legend>
      <div class="crm-section">
		<div class="label">{$form.from_email_address.label}</div>
		<div class="content">{$form.from_email_address.html}</div>
		<div class="clear"></div>
	  </div>
      <div class="crm-section" id="crm-mailing-settings-form-block-reply-to">
        {if $trackReplies}
		<div class="label">{ts}Reply-To{/ts}<span class="crm-marker">*</span></div>
		<div class="content">{ts}Auto-Generated{/ts}</div>
        {else}
        <div class="label">{$form.reply_to_address.label}</div>
        <div class="content">{$form.reply_to_address.html}</div>
        {/if}
		<div class="clear"></div>
	  </div>
      </fieldset>
      <fieldset>
      <legend>{ts}Exclude Groups{/ts}</legend>
      <div class="crm-section">
		<div class="label">{$form.excludeGroups.label}&nbsp;{help id="exclude-group" file="CRM/Mailing/Form/Group.hlp"}</div>
		<div class="content">{$form.excludeGroups.html}</div>
		<div class="clear"></div>
	  </div>
      {if $mailingCount > 0}
      <div class="crm-section">
		<div class="label">{$form.includeMailings.label}&nbsp;{help id="include-mailings" file="CRM/Mailing/Form/Group.hlp"}</div>
		<div class="content">{$form.includeMailings.html}</div>
		<div class="clear"></div>
	  </div>
      <div class="crm-section">
		<div class="label">{$form.excludeMailings.label}&nbsp;{help id="exclude-mailings" file="CRM/Mailing/Form/Group.hlp"}</div>
		<div class="content">{$form.excludeMailings.html}</div>
		<div class="clear"></div>
	  </div
      {**
     <!--   commmented the include and exclude mailings
        <tr class="crm-mailing-group-form-block-includeMailings"><td class="label">{$form.includeMailings.label} {help id="include-mailings"}</td></tr>
        <tr class="crm-mailing-group-form-block-includeMailings"><td>{$form.includeMailings.html}</td></tr>
        <tr class="crm-mailing-group-form-block-excludeMailings"><td class="label">{$form.excludeMailings.label} {help id="exclude-mailings"}</td></tr>
        <tr class="crm-mailing-group-form-block-excludeMailings"><td>{$form.excludeMailings.html}</td></tr>
     -->
     **}
      {/if}
      </fieldset>
	</div>
   </div>
   <!--<div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
	<div class="crm-accordion-header">
	<div class="icon crm-accordion-pointer"></div>
		{ts}Schedule or Send{/ts}
	</div>
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
   </div>-->
   {include file="CRM/Mailing/Form/InsertTokens.tpl"}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{* Note we can't use display attribute due to chrome bug*}
<div id='myTemplate' style="visibility: hidden; width: 0px; height: 0px">
  {if $reuse_message_template}
    {$reuse_message_template}
    {assign var=htmlMessageFound value=true}
  {/if}
</div>
</div>

{* JQUERY/JAVASCRIPT *}
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

  #cke_1_top, #helphtml {
    display: none !important;
  }

</style>
<script type="text/javascript">
  // Turn off automatic editor creation first.
  // @madav- edit message template with inline editor in popup window
  // @priyanka - we are no more using pop up for editing content
  //start
  cj(document).ready(function(){
    //Disable CKEDITOR in the beginning so it doesn't get applied to the iframe body
    CKEDITOR.config.readOnly = true;

    //var usedTemplateID = cj('#template').val();
    var usedTemplateID = "{/literal}{$htmlMessageFound}{literal}";
    if( usedTemplateID ){
        //my_dialog_callBack();
    }
  });

  cj(document).ajaxComplete(function(event, xhr, settings){
    if (settings.url.indexOf("civicrm/ajax/template") >= 0) {
    }
  });

  cj('.crm-html_email-accordion').attr('style', 'display:none;');
  cj('#template').change(function(){
    cj(document).ajaxSuccess(function(){
      cj('#cke_html_message').find('iframe').attr("id", "myframe");
      cj('iframe').parent().parent().parent().parent().parent().parent().css('display', 'block');

      //Make the following content editable
      CKEDITOR.config.readOnly = false;
      cj("iframe").contents().find('p, h1, h2, h3, div').each(function(){
          cj(this).attr("contenteditable","true");
          CKEDITOR.inline(this);
      });
    });
  });

cj( function($) {
    if (!cj('#override_verp').attr('checked')){
        cj('#crm-mailing-settings-form-block-forward_replies,#crm-mailing-settings-form-block-auto_responder,#crm-mailing-settings-form-block-reply-to').hide();
    }
    cj('#override_verp').click(function(){
        cj('#crm-mailing-settings-form-block-forward_replies,#crm-mailing-settings-form-block-auto_responder,#crm-mailing-settings-form-block-reply-to').toggle();
        if (!cj('#override_verp').attr('checked')) {
             cj('#forward_replies').attr('checked',false);
             cj('#auto_responder').attr('checked',false);
        }
    });

    // Format the campaign label/field HTML. so that it aligns to the rest of the page
    cj('#campaign_id').prev().wrap('<div class="label" />');
    cj('#campaign_id').wrap('<div class="content" />');
    cj('#campaign_id').after(cj('#crm-mailing-settings-form-block-campaign .helpicon'));


    if ( cj('#editMessageDetails').is(':visible') ) {
        cj('#editMessageDetails').hide();
    }
    //@madav - to make default values in quick buil mail form
    var headerId = "{/literal}{$headerId}{literal}";
    var footerId = "{/literal}{$footerId}{literal}";
    //var defaultEmailAddress = "{/literal}{$defaultEmailAddress}{literal}";
    cj('#header_id').val(headerId);
    cj('#footer_id').val(footerId);
    //cj('#open_tracking').attr('checked', 'checked');
    cj('#from_email_address').val('1');
    cj('#editMessageDetails').hide();
});

</script>
{/literal}
