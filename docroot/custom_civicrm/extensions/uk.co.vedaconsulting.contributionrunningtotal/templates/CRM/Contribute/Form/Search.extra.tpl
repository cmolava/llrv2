
	<div id="contributionSearch" class="crm-search-results">
        {include file="CRM/Contribute/Form/Selector.extra.tpl" context="Search"}
  </div>
  <!-- / floating tag  total div     
  <div id = "floating_total_amount" style=" display:none; position: absolute; right: 0; position: fixed; top: 550px; background: #FFFFCC; border: 1px solid #CFCEC3; padding: 10px; border-radius: 5px;"><span style=" float: right; width: 185px; text-align: center;"> </span>
  </div>-->
  <!-- / floating tag  fee div    
  <div id = "floating_fee_amount" style=" display:none; position: absolute; right: 0; position: fixed; top: 550px; background: #FFFFCC; border: 1px solid #CFCEC3; padding: 10px; border-radius: 5px;"><span style=" float: right; width: 185px; text-align: center;"> </span>
  </div> -->
  <!-- / floating tag  net div    
  <div id = "floating_net_amount" style=" display:none; position: absolute; right: 0; position: fixed; top: 550px; background: #FFFFCC; border: 1px solid #CFCEC3; padding: 10px; border-radius: 5px;"><span style=" float: right; width: 185px; text-align: center;"> </span>
  </div> -->
  

 <table>
   <tbody>
    <tr id ='tablerow'>
      <td id = "floating_total_amount" style=" display:none; position: absolute; right: 0; position: fixed; top: 550px; background: #FFFFCC; border: 1px solid #CFCEC3; padding: 10px; border-radius: 5px;"><span style=" float: right; width: 185px; text-align: center;"> </span>
                    </td>
      <td id = "floating_fee_amount" style=" display:none; position: absolute; right: 0; position: fixed; top: 550px; background: #FFFFCC; border: 1px solid #CFCEC3; padding: 10px; border-radius: 5px;"><span style=" float: right; width: 185px; text-align: center;"> </span>
                    </td>
      <td id = "floating_net_amount" style=" display:none; position: absolute; right: 0; position: fixed; top: 550px; background: #FFFFCC; border: 1px solid #CFCEC3; padding: 10px; border-radius: 5px;"><span style=" float: right; width: 185px; text-align: center;"> </span>
                    </td>
    </tr>
   </tbody>
 </table>
  <style type="text/css">
{literal}
  #floating_total_amount {
    color: rgb(0,255,0);
  }
  #floating_fee_amount {
    color: rgb(255,0,0);
  }
  #floating_net_amount {
    color: rgb(0,0,255);
  }
       
  {/literal}
</style>