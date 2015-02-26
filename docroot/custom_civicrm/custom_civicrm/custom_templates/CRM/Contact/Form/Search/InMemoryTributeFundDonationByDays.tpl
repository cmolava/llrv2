<!-- Customised Civi File .tpl file invoked: custom_civicrm/custom_templates/CRM/Contact/Form/Search/InMemoryTributFundDonationByDays.tpl Call via form.tpl if we have a form in the page. -->
<div class="crm-block crm-form-block crm-contact-custom-search-form-block">
  <div class="crm-accordion-wrapper crm-custom_search_form-accordion {if $rows}crm-accordion-closed{else}crm-accordion-open{/if}">

    <div class="crm-accordion-header crm-master-accordion-header">
      <div>
        {ts}Edit Search Criteria{/ts}
      </div>
    </div>

    <div class="crm-accordion-body">
      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="top"}
      </div>
      <table class="form-layout-compressed">
        {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
        {foreach from=$elements item=element}
        <tr class="crm-contact-custom-search-form-row-{$element}">
          <td class="label">
            {$form.$element.label}
          </td>
          {if $element eq 'start_date'}
          <td>
            {include file="CRM/common/jcalendar.tpl" elementName=start_date}
          </td>
          {else}
          <td>
            {$form.$element.html}
          </td>
          {/if}
        </tr>
        {/foreach}
      </table>
      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
      </div>
    </div>
    <!-- /.crm-accordion-body -->

  </div>
  <!-- /.crm-accordion-wrapper -->

</div>
<!-- /.crm-form-block -->

{if $rowsEmpty || $rows}
<div class="crm-content-block">
  {if $rowsEmpty}
    {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
  {/if}

  {if $summary}
    {$summary.summary}: {$summary.total}
  {/if}

  {if $rows}
  <div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}
    <div class="crm-search-tasks">
      {*include file="CRM/Contact/Form/Search/ResultTasks.tpl"*}
    </div>

    <div class="crm-search-results">
      {include file="CRM/common/pager.tpl" location="top"}

      {if $atoZ}
        {include file="CRM/common/pagerAToZ.tpl"}
      {/if}

      {strip}
      <table class="selector" summary="{ts}Search results listings.{/ts}">
        <thead class="sticky">
          <tr>
            {foreach from=$columnHeaders item=header}
            <th scope="col">
              {if $header.sort}
                {assign var='key' value=$header.sort}
                {$sort->_response.$key.link}
              {else}
                {$header.name}
              {/if}
            </th>
            {/foreach}
          </tr>
        </thead>

        {counter start=0 skip=1 print=false}
        {foreach from=$rows item=row}
        <tr id='rowid{$row.contact_id}' class="{cycle values="odd-row,even-row"}">
          {foreach from=$columnHeaders item=header}
            {assign var=fName value=$header.sort}
            {if $fName eq 'sort_name'}
              <td>
                <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.sort_name}</a>
              </td>
            {else}
              <td>
                {$row.$fName}
              </td>
            {/if}
          {/foreach}
        </tr>
        {/foreach}
      </table>
      {/strip}

      <script type="text/javascript">
        var fname = "{$form.formName}";	
        on_load_init_checkboxes(fname);
      </script>

      {include file="CRM/common/pager.tpl" location="bottom"}
    </div>
    <!-- /.crm-search-results -->
  </div>
  <!-- /.crm-results-block -->
  {/if}
</div>
<!-- /.crm-content-block -->
{/if}

{literal}
<script type="text/javascript">
  cj(function() {
     cj().crmAccordions(); 
  });
</script>
{/literal}