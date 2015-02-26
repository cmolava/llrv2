{if $smarty.get.scheduled eq 'false'}
{literal}
<script>
cj(document).ready(function(){
    cj('#Search > table > tbody  > tr').each(function() {
        var currentId = cj(this).attr('id');
        var temp = currentId.split('_');
        var mailingId = temp[1];
        
        var actionhtml = cj(this).find('td').eq(-1).html();
        actionhtml = actionhtml.replace("<span>", "");
        actionhtml = actionhtml.replace("</span>", "");
        actionhtml = actionhtml.replace(">Continue</a>", ">Continue - full</a>");
        
        var path = CRM.url('civicrm/quickbulkemail?mid='+mailingId+'&continue=true&reset=1');
        
        var continueSimple = '<a title="Continue - simple" class="action-item action-item-first" href="'+path+'">Continue - simple</a>';
        actionhtmlFull = '<span>' + continueSimple + actionhtml + '</span>';
        
        cj(this).find('td').eq(-1).html(actionhtmlFull);
        //cj(this).find('td').eq(5).after('<td>' + list_name + '</td><td>' + group_name + '</td>');
    });
});
</script>
{/literal}    
{/if}