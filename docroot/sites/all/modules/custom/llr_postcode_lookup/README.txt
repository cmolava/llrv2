LLR custom module that enables use of the Experian QAS postcode lookup
service for UK postcodes.

You can respond to the address form being populated event

        $(document).bind('llr_postcode_lookup.targetFormPopulated', function() {
            // Respond to the event here
        });


Details about the service can be found at:

  http://www.qas.co.uk/postcode-search.htm
  
Other resources:

  http://support.qas.com/search-results.htm?transactionId=&q=PHP
  http://support.qas.com/pro_on_demand_php_sample_code_1700.htm
  http://www.qas.com/downloads/pdf/support/v6/pro%20web/webrefcommon.pdf
  http://support.qas.com/proweb

TODO:
- Search for @todo in code
- This functionality is only available for UK postcodes. So any form that this 
is required to be integrated into needs to conditionally show/hide this 
dependant on the users selected value for 'country'