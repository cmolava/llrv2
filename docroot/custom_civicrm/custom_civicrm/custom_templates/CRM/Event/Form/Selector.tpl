<!-- Customised Civi File .tpl file invoked: custom_civicrm/custom_templates/CRM/Event/Form/Selector.tpl Call via form.tpl if we have a form in the page. -->
{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="top"}
{/if}

{strip}
<table class="selector">
<thead class="sticky">
    <tr>
    {if ! $single and $context eq 'Search' }
      <th scope="col" title="Select Rows">{$form.toggleSelect.html}</th> 
    {/if}
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
  <tr id='rowid{$row.participant_id}' class="{cycle values="odd-row,even-row"} crm-event crm-event_{$row.event_id}">
     {if ! $single }
        {if $context eq 'Search' }       
            {assign var=cbName value=$row.checkbox}
            <td>{$form.$cbName.html}</td> 
        {/if}	
	<td class="crm-participant-contact_type">{$row.contact_type}</td>
    	<td class="crm-participant-sort_name"><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}" title="{ts}View contact record{/ts}">{$row.sort_name}</a></td>
    {/if}

    <td class="crm-participant-event_title"><a href="{crmURL p='civicrm/event/info' q="id=`$row.event_id`&reset=1"}" title="{ts}View event info page{/ts}">{$row.event_title}</a>
        {if $contactId}<br /><a href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$row.event_id`"}" title="{ts}List participants for this event (all statuses){/ts}">({ts}participants{/ts})</a>{/if}
    </td> 
    {assign var="participant_id" value=$row.participant_id}
    {if $lineItems.$participant_id}
        <td>
        {foreach from=$lineItems.$participant_id item=line name=lineItemsIter}
	    {if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if}: {$line.qty}
            {if ! $smarty.foreach.lineItemsIter.last}<br />{/if}
        {/foreach}
        </td>
    {else}
        <td class="crm-participant-participant_fee_level">{if !$row.paid && !$row.participant_fee_level} {ts}(no fee){/ts}{else} {$row.participant_fee_level}{/if}</td>
    {/if}
    <td class="right nowrap crm-paticipant-participant_fee_amount">{$row.participant_fee_amount|crmMoney:$row.participant_fee_currency}</td>
    <td class="crm-participant-participant_register_date">{$row.participant_register_date|truncate:10:''|crmDate}</td>	
    <td class="crm-participant-event_start_date">{$row.event_start_date|truncate:10:''|crmDate}
        {if $row.event_end_date && $row.event_end_date|date_format:"%Y%m%d" NEQ $row.event_start_date|date_format:"%Y%m%d"}
            <br/>- {$row.event_end_date|truncate:10:''|crmDate}
        {/if}
   </td>
    <td class="crm-participant-participant_status crm-participant_status_{$row.participant_status_id}">{$row.participant_status}</td>
    <td class="crm-participant-participant_role">{$row.participant_role_id}</td>
    <td>{$row.action|replace:'xx':$participant_id}</td>
   </tr>
   
   
   {if $smarty.get.q eq 'civicrm/contact/view/participant'}

        {php}
            $participant = $this->get_template_vars('row');
            $participant_id = $participant['participant_id'];
            $event_id = $participant['event_id'];
            $team_sql = "SELECT c_team.display_name as team_name, c_team.id as team_id FROM civicrm_value_fundraising_team_data_130 ftd
            JOIN civicrm_contact c_team ON c_team.id = ftd.entity_id
            JOIN civicrm_relationship r ON r.contact_id_a = ftd.entity_id
            JOIN civicrm_contact c_member ON c_member.id = r.contact_id_b
            WHERE c_member.id = {$participant['contact_id']}
            AND ftd.event_id_569 = {$event_id}
            AND relationship_type_id IN (814, 815)";
            $team_dao = CRM_Core_DAO::executeQuery($team_sql);
            if ($team_dao->fetch()) {
                $team_name = $team_dao->team_name;
                $team_id = $team_dao->team_id;
            }
            if ($team_name) {
                echo "<tr><td>Team Name </td><td>";
                echo $team_name;
                echo "</td></tr>";
                echo "<tr><td>Team Members</td><td>";
                $member_sql = "SELECT c_member.display_name, c_member.id AS contact_id FROM civicrm_value_fundraising_team_data_130 ftd
                JOIN civicrm_contact c_team ON c_team.id = ftd.entity_id
                JOIN civicrm_relationship r ON r.contact_id_a = ftd.entity_id
                JOIN civicrm_contact c_member ON c_member.id = r.contact_id_b
                WHERE event_id_569 = {$event_id}
                AND c_team.id = {$team_id}
                AND relationship_type_id IN (814, 815)";
                $member_dao = CRM_Core_DAO::executeQuery($member_sql);
                while ($member_dao->fetch()) {
                    $display_name = $member_dao->display_name;
                    $contact_id = $member_dao->contact_id;
                    $url = CRM_Utils_System::url( 'civicrm/contact/view', "reset=1&cid={$contact_id}"  );
                    echo "<a href='$url'>" . $display_name . "</a><br />";
                }
                echo "</td></tr>";
            }
        {/php}
       
   {/if}
   
  {/foreach}
{* Link to "View all participants" for Dashboard and Contact Summary *}
{if $limit and $pager->_totalItems GT $limit }
  {if $context EQ 'event_dashboard' }
    <tr class="even-row">
    <td colspan="10"><a href="{crmURL p='civicrm/event/search' q='reset=1'}">&raquo; {ts}Find more event participants{/ts}...</a></td></tr>
    </tr>
  {elseif $context eq 'participant' }  
    <tr class="even-row">
    <td colspan="7"><a href="{crmURL p='civicrm/contact/view' q="reset=1&force=1&selectedChild=participant&cid=$contactId"}">&raquo; {ts}View all events for this contact{/ts}...</a></td></tr>
    </tr>
  {/if}
{/if}
</table>
{/strip}

{if $context EQ 'Search'}
 <script type="text/javascript">
 {* this function is called to change the color of selected row(s) *}
    var fname = "{$form.formName}";	
    on_load_init_checkboxes(fname);
 </script>
{/if}

{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="bottom"}
{/if}
