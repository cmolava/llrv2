{literal}
  <script type="text/javascript">
    var storeAmount = new Array();
    cj(document).ready(function(){
      var currencySymbol = "{/literal}{$config->defaultCurrencySymbol}{literal}"; 
      cj("input[type='checkbox']").on("click",function(){
          var position          = cj(this).offset();
          var fixedLeft         = position.left + 50 ;
          var contnetfixedLeft  = position.left + 50 ;
          var contfeefixedLeft  = position.left + 50 ;
          var fixedTop          = position.top - cj(document).scrollTop() ;
          var contnetfixedTop   = position.top - cj(document).scrollTop() - 86 ;
          var contfeefixedTop   = position.top - cj(document).scrollTop() - 43 ;
          var dystyle           = "display:none; position: absolute; left: " + fixedLeft + "px; top: " + fixedTop + "px; position: fixed; background: #FFFFCC; border: 1px solid #CFCEC3; padding: 10px; border-radius: 5px";
          var contnetdystyle    = "display:none; position: absolute; left: " + contnetfixedLeft + "px; top: " + contnetfixedTop + "px; position: fixed; background: #FFFFCC; border: 1px solid #CFCEC3; padding: 10px; border-radius: 5px";
          var contfeedystyle    = "display:none; position: absolute; left: " + contfeefixedLeft + "px; top: " + contfeefixedTop + "px; position: fixed; background: #FFFFCC; border: 1px solid #CFCEC3; padding: 10px; border-radius: 5px";
          cj("#floating_total_amount").attr("style", dystyle);
          cj("#floating_net_amount").attr("style", contnetdystyle);
          cj("#floating_fee_amount").attr("style", contfeedystyle);
          cj("#floating_total_amount").show();
          cj("#floating_net_amount").show();
          cj("#floating_fee_amount").show();
          if(cj(this).attr('id') == 'toggleSelect'){
            if(cj(this).prop("checked") == true){
              // toggle check condition true code
              var totalAmount = 0;
              var netAmount   = 0;
              var feeAmount   = 0;
              var obj         = cj('.crm-form-checkbox').val('id');
              jQuery.each( obj, function( key, value ) {
                var splitId = value.id.split('_');
                if(splitId[0] == 'mark'){
                  var explodetotalAmount  = cj('#rowid'+splitId[2]+' td.crm-contribution-amount span').text().split(" ");
                  var contributionID      = splitId[2];
                  CRM.api3('Contribution', 'getsingle', {
                  "sequential": 1,
                  "id": contributionID
                  }).done(function(result) {
                    // do something
                      var contriNetamount = parseFloat( result['net_amount']);
                      var contriFeeamount = parseFloat( result['fee_amount']);
                      if(contriNetamount > 0){
                          netAmount +=  contriNetamount;
                      }
                      if(contriFeeamount > 0){
                          feeAmount +=  contriFeeamount;
                      }
                      cj("#floating_net_amount").html("Net Amount:" + currencySymbol +" "+netAmount.toFixed(2));
                      cj("#floating_fee_amount").html("Fee Amount:" + currencySymbol +" "+feeAmount.toFixed(2));
                  });
                  var amount   = parseFloat(explodetotalAmount[1]);
                  totalAmount += amount;
                }
              });
              cj("#floating_total_amount").html("Total Amount:" + currencySymbol +" "+totalAmount.toFixed(2));
          }
          // toggle uncheck condition false code
          if(cj(this).prop("checked") == false){
            cj('#floating_total_amount').hide();
            cj("#floating_total_amount").empty();
            cj('#floating_net_amount').hide();
            cj("#floating_net_amount").empty();
            cj('#floating_fee_amount').hide();
            cj("#floating_fee_amount").empty();
          }
        }else{
          //single checkbox check condition true code
          if(cj(this).prop("checked") == true){
            var selectedBox           = cj(this);
            var netAmount             = 0;
            var feeAmount             = 0;
            var selectedId            = cj(selectedBox).attr('id');
            var splitId               = selectedId.split("_");
            var contributionID        = splitId[2];
            //net amount calculation
            //API for get contribution net amount 
            CRM.api3('Contribution', 'getsingle', {
              "sequential": 1,
                "id": contributionID
            }).done(function(result) {
              // do something
              var contriNetamount = parseFloat(result['net_amount']);
              var selectedNetValue      = cj('#floating_net_amount').text().split(" ");
              var totalSelectednetValue = parseFloat(selectedNetValue[2]);
              if(isNaN(totalSelectednetValue)){
                if(contriNetamount > 0){
                  netAmount += contriNetamount;
                  cj("#floating_net_amount").html("Net Amount:" + currencySymbol +" "+netAmount.toFixed(2));
                }else if(isNaN(contriNetamount)){
                 cj('#floating_net_amount').hide();
                 cj("#floating_net_amount").empty();
                }
              }else{
                if(contriNetamount > 0 ){
                netAmount =  totalSelectednetValue + contriNetamount;
                cj("#floating_net_amount").html("Net Amount:" + currencySymbol +" "+netAmount.toFixed(2));
                }else if(isNaN(contriNetamount)){
                  var netResult = cj('#floating_net_amount').text().split(" ");
                  netAmount     = parseFloat(netResult[2]);
                  if(netAmount){
                    cj("#floating_net_amount").html("Net Amount:" + currencySymbol +" "+netAmount.toFixed(2));
                  }else{
                    cj('#floating_net_amount').hide();
                    cj("#floating_net_amount").empty();
                  }
                }
              }
            });
            //fee amount calculation
            //API for get contribution fee amount 
            CRM.api3('Contribution', 'getsingle', {
               "sequential": 1,
               "id": contributionID
            }).done(function(result) {
              // do something
              var contriFeeamount = parseFloat(result['fee_amount']);
              var selectedFeeValue      = cj('#floating_fee_amount').text().split(" ");
              var totalSelectedfeeValue = parseFloat(selectedFeeValue[2]);
              if(isNaN(totalSelectedfeeValue)){
                if(contriFeeamount > 0 ){
                  feeAmount += contriFeeamount;
                  cj("#floating_fee_amount").html("Fee Amount:" + currencySymbol +" "+feeAmount.toFixed(2));
                }else if(isNaN(contriFeeamount)){
                 cj('#floating_fee_amount').hide();
                 cj("#floating_fee_amount").empty();
                }
              }else{
                if(contriFeeamount > 0 && contriFeeamount != null){
                  feeAmount =  totalSelectedfeeValue + contriFeeamount;
                  cj("#floating_fee_amount").html("Fee Amount:" + currencySymbol +" "+feeAmount.toFixed(2));
                }else if(isNaN(contriFeeamount)){
                  var feeResult  = cj('#floating_fee_amount').text().split(" ");
                  feeAmount      = parseFloat(feeResult[2]);
                  if(feeAmount){
                    cj("#floating_fee_amount").html("Fee Amount:" + currencySymbol +" "+feeAmount.toFixed(2));
                  }else{
                   cj('#floating_fee_amount').hide();
                   cj("#floating_fee_amount").empty();
                  }
                }                  
              }
            });
            //total amount calculations
            var selectedValue       = cj('#floating_total_amount').text().split(" ");
            var totalSelectedValue  = parseFloat(selectedValue[2]);
            var explodetotalAmount  = cj('#rowid'+splitId[2]+' td.crm-contribution-amount span').text().split(" ");
            var amount              = parseFloat(explodetotalAmount[1]);
            var totalAmount         = amount + totalSelectedValue;
            if(totalSelectedValue == 0){
              cj("#floating_total_amount").html("Total Amount:" + currencySymbol +" "+totalAmount.toFixed(2));
            }else{
              if(isNaN(totalSelectedValue)){
                cj("#floating_total_amount").html("Total Amount:" + currencySymbol +" "+amount.toFixed(2));
              }else{
                cj("#floating_total_amount").html("Total Amount:" + currencySymbol +" "+totalAmount.toFixed(2));
              }
            }
          }
          //single checkbox check condition false code
          if(cj(this).prop("checked") == false){
            var unselectedBox       = cj(this);
            var selectedId          = cj(unselectedBox).attr('id');
            var splitId             = selectedId.split("_");
            var contributionID      = splitId[2];
            var netAmount           = 0;
            var feeAmount           = 0;
            //uncheck net amount calculations
            var netminusResult      = cj('#floating_net_amount').text().split(" ");
            var totalnetminusResult = parseFloat(netminusResult[2]);
            if(totalnetminusResult){
              CRM.api3('Contribution', 'getsingle', {
                "sequential": 1,
                "id": contributionID
              }).done(function(result) {
                var contriNetamount = parseFloat(result['net_amount']);
                if(contriNetamount > 0){
                   netAmount =  totalnetminusResult - contriNetamount;
                   if(netAmount == 0){
                     cj('#floating_net_amount').hide();
                     cj("#floating_net_amount").empty();
                   }
                }else if(isNaN(contriNetamount)){
                  netAmount = totalnetminusResult;
                }else if(isNaN(netAmount) || netAmount == 0){
                  cj('#floating_net_amount').hide();
                  cj("#floating_net_amount").empty();
                }
                cj("#floating_net_amount").html("Net Amount:" + currencySymbol +" "+netAmount.toFixed(2));
              }); 
            }else if(isNaN(totalnetminusResult) || totalnetminusResult == 0){
              cj('#floating_net_amount').hide();
              cj("#floating_net_amount").empty();
            }
            //uncheck fee amount calculations
            var feeminusResult      = cj('#floating_fee_amount').text().split(" ");
            var totalfeeminusResult = parseFloat(feeminusResult[2]);
            if(totalfeeminusResult){
              CRM.api3('Contribution', 'getsingle', {
                "sequential": 1,
                "id": contributionID
              }).done(function(result) {
                var contriFeeamount = parseFloat(result['fee_amount']);
                if(contriFeeamount > 0){
                  feeAmount =  totalfeeminusResult - contriFeeamount;
                  if(feeAmount == 0){
                    cj('#floating_fee_amount').hide();
                    cj("#floating_fee_amount").empty();
                  }
                }else if(isNaN(contriFeeamount)){
                  feeAmount = totalfeeminusResult;
                }else if(isNaN(feeAmount) || feeAmount == 0){
                  cj('#floating_fee_amount').hide();
                  cj("#floating_fee_amount").empty();
                }
                  cj("#floating_fee_amount").html("Fee Amount:" + currencySymbol +" "+feeAmount.toFixed(2));
              }); 
            }else if(isNaN(totalfeeminusResult) || totalfeeminusResult == 0 ){
              cj('#floating_fee_amount').hide();
              cj("#floating_fee_amount").empty();
            }
            //uncheck total amount calculations
            var explodetotalAmount  = cj('#rowid'+splitId[2]+' td.crm-contribution-amount span').text().split(" ");
            var amount              = parseFloat(explodetotalAmount[1]);
            var minusResult         = cj('#floating_total_amount').text().split(" ");
            var totalminusResult    = parseFloat(minusResult[2]);
            var removeRow           = parseFloat(totalminusResult) - parseFloat(amount);
            if(isNaN(removeRow) || removeRow == 0){
              cj('#floating_total_amount').hide();
              cj("#floating_total_amount").empty();
            }else{
              cj("#floating_total_amount").html("Total Amount:" + currencySymbol +" "+removeRow.toFixed(2));
            }
          }
        }
      });
      cj('td.nowrap').click(function(){
        cj('#floating_total_amount').hide();
        cj("#floating_total_amount").empty();
        cj('#floating_fee_amount').hide();
        cj("#floating_fee_amount").empty();
        cj('#floating_net_amount').hide();
        cj("#floating_net_amount").empty();
      });
    });
  </script>
{/literal}