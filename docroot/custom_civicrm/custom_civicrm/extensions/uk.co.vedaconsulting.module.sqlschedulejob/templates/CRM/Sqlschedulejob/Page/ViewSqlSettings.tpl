<div id="renewal_sql_settings_display_div">
		<div class="help">
				<p>
				{ts}Use this section to add/edit SQLs to be executed using CiviCRM Scheduled Jobs. The SQLs added here will be executed in the assigned weight order when the related scheduled job runs.{/ts}
				</p>
		</div>
		<input type="button" name="go_to_add_settings" value="Add SQL" onclick="parent.location.href='{$redirectURL}'" />
    {if $settingsData}
        <table>
            <thead>
            <tr>
                <!--<th>ID.No.</th>-->
                <th>SQL</th>
                <th>Weight</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$settingsData item=data}
                <tr>
                    <!--<td>{$data.id}</td>-->
                    <td>{$data.sql}</td>
                    <td>{$data.weight}</td>
                    <td>{$data.edit} / {$data.delete}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    {else}
			<div class="messages status no-popup">
			<div class="icon inform-icon"></div>
			No SQLs have beed added You can <a href="{$redirectURL}" accesskey="N">add one</a>.</div>	
    {/if}
</div>