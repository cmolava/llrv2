<div class="crm-block crm-form-block crm-trackmailingsettings-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

<fieldset>
    <table class="form-layout">
        <tr class="crm-trackmailingsettings-form-block-unsubscribe-redirect-url">
          <td class="label">{$form.unsubscribe_redirect_url.label}</td>
          <td>
            {$form.unsubscribe_redirect_url.html}
          </td>
        </tr>
   </table>

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</fieldset>

</div>