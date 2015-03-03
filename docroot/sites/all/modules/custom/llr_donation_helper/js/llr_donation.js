
/**
 * CiviCRM jQuery for Civi/webforms
 */
if ( typeof cj === 'function' ) {
  cj(function($) {
    var donateWebForm = $('form.webform-donate-single,form.webform-donate-monthly'),
      settings = Drupal.settings.LLRDonate || {},
      amountFieldProcessed = 0;
    if ( donateWebForm.length ) {
      //If donation amount field is not populated, show it.
      var totalAmtField = $('.webform-component--civicrm-1-contribution-1-contribution-total-amount');
      if ( ! amountFieldProcessed ) {  
        if ( ! totalAmtField.find('input[type=text]').val() ) {
          totalAmtField.show(); 
          amountFieldProcessed = true;
        }
      }
         //Any modifications to payment blocks need to be performed after they are loaded. 
      $('#billing-payment-block').bind('crmFormLoad', function() {
           //Change card legend text
           $('.credit_card_info-group legend', this).text('Card Details');
           //Populate address 
           
           var addr = $('fieldset.billing_name_address-group', $(this)),
              billingVals = settings['billing_name_address_fields'] || [],
              input = null,
              showAddress = false;
              console.log(settings);
              
           for ( key in billingVals ) {
             input = $('#' + key, addr);
             if ( ! billingVals.hasOwnProperty(key) ) {
               continue;
             }
             if ( ! input.length ) {
               showAddress = true;
               continue;
             }
             input.val(billingVals[key]);
           }
           //We hide billing address after we are sure it is populated
           var addrSection = addr.children('div.billing_name_address-section');
           addrSection.toggle(showAddress && billingVals);
           //Allow billing address to be changed
           addr.children('legend').text('Change Billing Name and Address').click(function() {
              showAddress = ! showAddress;
              addrSection.toggle(showAddress);
               }
           );
           //Always hide the billing items table
           $('#wf-crm-billing-items').hide();
         });//end bind(crmFormLoad..
       }
  }); 
}
//end civi jquery

(function($, Drupal) {
  
  //Postcode lookup and address
  $(document).ready(function (){
    //settings provided by llr_postcode_lookup module
   var settings = Drupal.settings['llrPostcodeLookup'] || [],
     $donateForm = $('form.webform-donate-single, form.webform-donate-monthly'),
     //postcode lookup input fields which have been injected into the form
     $pclInputs = $('.llr-postcode-lookup-input', $donateForm),
     $pclInputsWpr,
     $pclManual = $('.llr-postcode-lookup-input-manual-entry', $donateForm),
     $addrFields = $(),
     $countrySelect = $('select[id$=address-country-id]', $donateForm);
    if ( ! $donateForm.length || ! settings || ! $pclManual.length ) {
      return;
    }
    formId = $donateForm.attr('id');
    addrFieldIds = settings.forms[formId]['target']['formToQasFieldMap'];
    
    var fid, wpr;
    for ( fid in addrFieldIds ) {
      if ( addrFieldIds.hasOwnProperty(fid) ) {
        wpr = $('#' + fid).parents('div.form-item');
        $addrFields = $addrFields.add(wpr);
      }
    }
    //Inially hide address fields
    $addrFields.hide();

    //Show address fields to enter address manually,
    $pclManual.change(function() { 
      $addrFields.toggle($(this).val());
    });

    //Show address fields when populated by postcode lookup
    $(document).bind('llr_postcode_lookup.targetFormPopulated',function(e) {
      $addrFields.show();                
    });

    //Hide Postcode lookup fields when country is not UK 
    $pclInputsWpr = $pclInputs.parents('.form-item')
        .add('.llr-postcode-lookup-find-container');
    $countrySelect.change(function() {
     var cid = $(this).val(),
      name = $(this).find('option[value=' + cid +']').text();
      $pclManual.prop('checked', true).trigger('change');
      $pclInputsWpr.toggle('United Kingdom' == name);
    });
                   
  });// end postcode lookup ready()

  //Donation start page
  $(document).ready(function() {
    
    if ( $('form#llr-donation-donate-start-form').length) {
      //Bind body class to donation_options selection
      initDonationOptions();
      
      var amountOptions = $('div[id^=edit-donate-amount-options-] input[type=radio]'),
          amountOptionBlocks = blockifyRadios(amountOptions),
          //The amountText holds the actual donation amount.
          amountText = $('#edit-donate-amount');
          amountText.parent().find('label').hide();
      initAmountFacts(amountOptions, 'mouseenter mouseleave');
      
      //Set amount text whenever an amount is selected
      bindAmount(amountText, amountOptions);
      
      //Prevent badly formatted amount in text field.
      amountText.change(function(){
        $(this).val(formatAmount($(this).val()));
      }).trigger('change');
    }//form#llr-donation-donate-start-form
  }); //$(document).ready(..)end donation start page


//nudge on donation webform pages
 $(document).ready(function() {
    var donationWebForm = $('form.webform-donate-single,form.webform-donate-monthly'),
       amountInput = null,
       amount = 0,
       settings = Drupal.settings.LLRDonate | {},
       nudge = settings['nudge'] || null,
       nudgeForm = '',
       nudgeBox= null;
    if ( donationWebForm.length && nudge ) {
      initNudge();
    }

    function initNudge() {
      amountInput = $('input#edit-submitted-civicrm-1-contribution-1-contribution-total-amount');
      if ( ! amountInput.length || ! amountInput.val() ) {
        return;
      }
      amountInput.change( function(e) {
        //Replace title and header whenever donation amount changes
        var header = $('h1.title'),
          newTitle = header.text().replace(/£[0-9.]+/,'£' + $(this).val());  
        header.text(newTitle);
        $('head title').text(newTitle);  
      });//change
        
      //Validate nudge values 
      if ( parseFloat(amountInput.val()) + parseFloat(nudge.shortfall) != parseFloat(nudge.target_value) ) {
        nudge.shortfall = parseFloat(nudge.target_value) - parseFloat(amountInput.val());
        if ( nudge.shortfall <= 0 || nudge.shortfall >= parseFloat(nudge.threshold) ) {
          return;
        }
      }
      //Initialize the nudge display
      nudgeForm = '<form id="donate-nudge-form"><div>' + nudge.text +'</div><input type="button" id="go-on" value="' + nudge.button_text + '"/></form>';
      nudgeBox = supplementaryContentBox({
        id : 'donate-nudge-box',
        parent : $('#sidebar-first'),
        showDuration: 200
      });
      nudgeBox.show('With £' + nudge.target_value, nudgeForm);
      
      //Behaviour on nudge acceptance
      $('#donate-nudge-form #go-on').click(function() {
         amountInput.val(nudge.target_value).trigger('change');
         nudgeBox.hide(); 
         nudgeBox.show('You are amazing!', 'An extra £' + nudge.shortfall + ' has been added to your donation');
      });
    } 
  });//ready.. end donation webform



  /**
   *  Sets value of $textField to value of most recently checked radio in
   *  $radios.
   */
  function bindAmount($textField, $radios) {
    $radios.change(function(e) {  
        if ( $(this).prop('checked') ) {
            //When selected populate the main amount field
           
        console.log('item is checked triggering change in txt');
            $textField.val($(this).val()).trigger('change');
         }
    });
    //Clear selection when textField changes
   //Amount is manually adjusted, alter radios  
    $textField.blur(function() {
      $radios.each(function() {
        var check = $textField.val() == formatAmount($(this).val());
        if ( check != $(this).prop('checked') ) {
          $(this).prop('checked', check).trigger('change');
        }
        
      }); //each
    });//blur
  }//bindAmount
  
  /**
   * Formats a string to float precision 2, suitable for a cash amount.
   * If it won't go, returns empty string.
   */
  function formatAmount(val) {
    val = parseFloat(val).toFixed(2) || '';
    if ( isNaN(val) || 0 >= val ) {
      val = ''
    }
    return val;
  }
  
  /**
   * Set up display of facts  
   */
  function initAmountFacts(amountOptions, eventType) {
    var  amountSettings = Drupal.settings.LLRDonate,
      factBox = supplementaryContentBox({
        id : 'donatefact',
        parent : $('#sidebar-first'),
        showDuration: 200
      }),
      titleString = function(amount) {
        return 'Donate £' + amount;
      };
    amountOptions.each(function() {
      var subject = this.boundBlock || this,
          value = $(this).val();
      //display fact related to particular amount option
      for ( var i in amountSettings ) {
        if ( amountSettings.hasOwnProperty(i)
             && amountSettings[i].value == value ) {
          //We should be able to use .bind('mouseenter mouseleave') here but
          //the mouseenter() etc. seems to register the events more cleanly.
          if ( eventType == 'mouseenter mouseleave' ) {
              subject.mouseenter(amountSettings[i], function(event) {
                factBox.show(titleString(event.data.value), event.data.fact);
              });
              subject.mouseleave(function(e) {
                factBox.hide();          
              });
          }
          else {
            subject.bind(eventType, amountSettings[i], function(event){
              if ( event.target !== this ) {
                return;
              }
              if ( event.data ) {
                  factBox.show(titleString( event.data['value']), event.data['fact']);
              }
            });
          }
        }
      }
      //switch body class when option changed
      classSwitcher({
        targetElem : document.getElementsByTagName('body')[0],
        subjectElem : subject,
        eventType: eventType,
        className : 'donate-amount-' + value
      });
    });
  }//initAmountFacts

  /**
   * Initialize behaviour for the top-level donation options (single, monthly,
   * pay_in etc.)
   */
  function initDonationOptions() {
    var options = $('form#llr-donation-donate-start-form .form-item-donate-options input.form-radio'),
    conditionalItems = [];

    options.each(function() { 
      var showOnChecked = '.form-item-donate-amount-options-' + $(this).val().replace('_', '-');
      if ( $(showOnChecked).length ) {
        this.showOnChecked = $(showOnChecked);
        conditionalItems.push(showOnChecked);
      }
      if ( ! $(this).attr('checked') ) {
        $(showOnChecked).hide();
      }
      $(this).change(function(e) {
        $(conditionalItems.join()).hide();
          if ( typeof this.showOnChecked == 'object' ) {
          this.showOnChecked.toggle($(this).attr('checked'));
        }
      });
    });//options.each()
  }//initDonationOptions
  
  function supplementaryContentBox(params) {
    return $.extend({
       id : 'supplementary-content',
       parent : null,
       elem : null,
       showDuration: 0,
       init : function() {
         if ( 0 ===  $('#' + this.id, this.parent).length ) {
           this.elem = $(
              '<div id="'+ this.id + '"><h3 class="title"></h3>'
              + '<div class="content"></div></div>'
            ).appendTo(this.parent);
          } 
         this.elem.hide();
       },
       show : function(title, content) {
         if ( ! this.elem ) {
            this.init();
         }
         $('.title', this.elem).text(title);
         $('.content', this.elem).html(content);
         this.elem.show(this.showDuration);
         return this;
       },
       hide : function() { this.elem.hide(this.showDuration); }
    }, params);
  }
  
  /**
   * Make radios look nicer
   * We assume:
   *  - each radio input element has a sibling label and a div parent.
   *  - grouped in a container div.
   *
   */
  function blockifyRadios($radioSelects) {
    var defaultClass = 'select-block',
        selectedClass = 'select-block-selected',
        sets = [];
    $radioSelects.each(function(index) {
        var that = this,
            block = $(this).parent('div.form-item'),
            label = $('label', block),
            blockContainer = block.parent('div');
        $(this).change(function(e) {
          if ( this.checked != true ) {
             block.removeClass(selectedClass);
           }
           else {
             blockContainer.find('.' + defaultClass).removeClass(selectedClass);
             block.addClass(selectedClass);
           }
        }).hide();

        blockContainer.addClass('select-block-container');
        this.boundBlock = block;
        block.addClass(defaultClass)
          .click(function() {
             if ( ! $(that).attr('checked') ) { 
                $(that).attr('checked', true).trigger('change');    
             }
          });
       sets.push(block);
    });
    return sets;
  }//blockifyRadios

  /**
   * Binds the css class of a target element to an event on another element (subject element).
   *
   * Typical use would be to call multiple times with different subject elements
   * and the same target element. This will switch between
   *  
   * @params params : object with properties
   *   - targetElem : a dom element
   *   - subjectElem : a dom element
   *   - eventType : string denoting event to bind to
   *   - className :  string name of class. should be unique among the set of
   *   classes added to target.
   *   - callback : optional callback function with params:
   *        - subjectElem,
   *        - targetElem 
   */
  function classSwitcher(params) {
    var target =  params.targetElem,
        subject = params.subjectElem,
        className =  params.className,
        eventType = params.eventType,
        callback = params.callback;
    subject.bind(eventType, function(event) {
        if ( event.type == 'mouseleave' ) {
          $(target).removeClass(target.currentSwitchClass);
          target.currentSwitchClass = '';
        }
        else {
          switchClasses();
        }
        if ( callback ) {
          callback(subject, target);
        }
    });
   

    function switchClasses() { 
      if ( target.hasOwnProperty('currentSwitchClass') ) {
         $(target).removeClass(target.currentSwitchClass);
      }
        $(target).addClass(className);
        target.currentSwitchClass = className;
      }
   }// classSwitcher
}(jQuery, Drupal));//wrapper
