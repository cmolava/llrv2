<div class="crm-block crm-form-block crm-quickbulkemail-view-list">
<br />
<div id="button">
  <a href={crmURL p='civicrm/quickbulkemail'} class="button">New mailing</a>      
</div>
<br>
<br>
  <table class="selector" id="list_mailings_table">
      <thead class="sticky">
        <tr>
            {foreach from=$columnHeaders item=header}
              <th>{$header}</th>
            {/foreach}
        </tr>
      </thead>
      <tbody>
      {foreach from=$rows item=row}
        <tr>
          <td>{$row.name}</td>
          <td>{$row.status}</td>
          <td>{$row.created_by}</td>
          <td>{$row.scheduled_by}</td>
          <td>{$row.scheduled}</td>
          <td>{$row.start}</td>
          <td>{$row.end}</td> 
          <td>{$row.action}</td>
        </tr>
      {/foreach}
      </tbody> 
      <tfoot id='list_mailings_table_foot'>
       {foreach from=$columnHeaders item=header}
         {if $header neq 'Action'}
              <th></th>
          {/if}
        {/foreach}
          
      </tfoot>
  </table>
  </div>
  
{literal}
  <style>
    tfoot#list_mailings_table_foot select{
      width:100px;
    }
    
  </style>
  <script src="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js"></script>
  
  <script type='text/javascript'>
    cj.fn.dataTableExt.oApi.fnGetColumnData = function ( oSettings, iColumn, bUnique, bFiltered, bIgnoreEmpty ) {
    // check that we have a column id
    if ( typeof iColumn == "undefined" ) return new Array();
     
    // by default we only want unique data
    if ( typeof bUnique == "undefined" ) bUnique = true;
     
    // by default we do want to only look at filtered data
    if ( typeof bFiltered == "undefined" ) bFiltered = true;
     
    // by default we do not want to include empty values
    if ( typeof bIgnoreEmpty == "undefined" ) bIgnoreEmpty = true;
     
    // list of rows which we're going to loop through
    var aiRows;
     
    // use only filtered rows
    if (bFiltered == true) aiRows = oSettings.aiDisplay;
    // use all rows
    else aiRows = oSettings.aiDisplayMaster; // all row numbers
 
    // set up data array   
    var asResultData = new Array();
     
    for (var i=0,c=aiRows.length; i<c; i++) {
        iRow = aiRows[i];
        var aData = this.fnGetData(iRow);
        var sValue = aData[iColumn];
         
        // ignore empty values?
        if (bIgnoreEmpty == true && sValue.length == 0) continue;
 
        // ignore unique values?
        else if (bUnique == true && cj.inArray(sValue, asResultData) > -1) continue;
         
        // else push the value onto the result data array
        else asResultData.push(sValue);
    }
     
    return asResultData;
  };
 
 
function fnCreateSelect( aData )
{
    var r='<select><option value=""></option>', i, iLen=aData.length;
    for ( i=0 ; i<iLen ; i++ )
    {
        r += '<option value="'+aData[i]+'">'+aData[i]+'</option>';
    }
    return r+'</select>';
}
 
 
 cj(document).ready(function() {
    /* Initialise the DataTable */
    var oTable = cj('#list_mailings_table').dataTable( {
        "oLanguage": {
            "sSearch": "Search all columns:"
        }
     });
     
    /* Add a select menu for each TH element in the table footer */
    cj("tfoot th").each( function ( i ) {
        
        this.innerHTML = fnCreateSelect( oTable.fnGetColumnData(i) );
        cj('select', this).change( function () {
            oTable.fnFilter( cj(this).val(), i );
        } );
    });
 });
 </script>
{/literal}