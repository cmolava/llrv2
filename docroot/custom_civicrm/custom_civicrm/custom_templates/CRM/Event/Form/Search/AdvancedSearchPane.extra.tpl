<!-- Custom file, path: CRM/Event/Form/Search/AdvanceSearchPane.extra.tpl -->

{literal}
<script type="text/javascript">
var eventUrl = "{/literal}{crmURL p='civicrm/ajax/event' q='reset=1'}{literal}";
var size = "{/literal}{crmSetting name='search_autocomplete_count' group='Search Preferences'}{literal}";
cj('#event_name').autocomplete( eventUrl, { width : 280, selectFirst : false, matchContains: true, max: size
}).result( function(event, data, formatted) { cj( "input#event_id" ).val( data[1] );
  }).bind( 'click', function( ) { cj( "input#event_id" ).val(''); });

</script>
{/literal}
