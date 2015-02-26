{* HEADER *}

<!--<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>-->

<div class="help">
	<p>
	{ts}Use this form to add/edit SQLs to be executed using CiviCRM Scheduled Jobs.
	<br /><br /><strong>IMPORTANT:</strong> Make sure you validate the SQL for any syntax errors before adding here.
	<br /><br /> Can Use ';' to add mulitple sql. 
	{/ts}
	</p>
</div>

<div>
    <div class="label">{$form.existing_id.html}</div>
    <div class="clear"></div>
</div>
<div>
    <div class="label">{$form.sql_schedule.label}</div>
    <div class="content">{$form.sql_schedule.html}</div>
    <div class="clear"></div>
</div>
<div>
    <span>{$form.sql_schedule_weight.label}</span> &nbsp;&nbsp; <span>{$form.sql_schedule_weight.html} </span> &nbsp;&nbsp;
    <small>If given weight already exists or left blank, then maximum weight would be considered as this SQL's weight.</small>
    <div class="clear"></div>
</div>


{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{literal}
    <script type="text/javascript">
    </script>
{/literal}