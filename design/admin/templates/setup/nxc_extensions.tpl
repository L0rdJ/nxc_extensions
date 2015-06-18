{if and( is_set( $warning_messages), $warning_messages|count|ge(1) )}
	<div class="message-warning">
	<h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span> {'Problems detected during autoload generation:'|i18n( 'design/admin/setup/extensions' )}</h2>
	<ul>
	{foreach $warning_messages as $warning}
		<li><p>{$warning|break()}</p></li>
	{/foreach}
	</ul>
	</div>
{/if}

<form method="post" action={'/nxc_extensions/extensions'|ezurl}>

	<div class="context-block">

		{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
			<h1 class="context-title">{'Available extensions [%extension_count]'|i18n( 'design/admin/setup/extensions',, hash( '%extension_count', $available_extensions|count ) )}</h1>
			{* DESIGN: Mainline *}<div class="header-mainline"></div>
		{* DESIGN: Header END *}</div></div></div></div></div></div>

		{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">
		{if gt( $available_extensions|count(), 0 )}
		<table class="list" cellspacing="0">
			<thead>
				<tr>
					<th class="tight">{'Active'|i18n( 'design/admin/setup/extensions' )}</th>
					<th class="tight nxc-extensions-order">{'Order'|i18n( 'design/admin/setup/extensions' )}</th>
					<th>{'Name'|i18n( 'design/admin/setup/extensions' )}</th>
				</tr>
			</thead>

			<tbody id="nxc-extensions-list">
			{def $can_be_activated = true()}
			{foreach $available_extensions as $extension sequence array( 'bgdark', 'bglight' ) as $style}
				<tr class="{$style}">

					{* Status. *}
					{set $can_be_activated = true()}
					{foreach $extension.dependencies as $extension_name}
						{if eq( $available_extensions[$extension_name].is_activated, false() )}
							{set $can_be_activated = false()}
							{break}
						{/if}
					{/foreach}
					<td><input type="checkbox" name="ActiveExtensionList[]" value="{$extension.name|wash}" {if $extension.is_activated}checked="checked"{/if} {if eq( $can_be_activated, false() )}disabled="disabled"{/if} title="{'Activate or deactivate extension. Use the "Apply changes" button to apply the changes.'|i18n( 'design/admin/setup/extensions' )|wash}" /></td>

					{* Order. *}
					<td class="nxc-extensions-order"><input type="input" size="3" name="ExtensionOrders[{$extension.name|wash}]" value="{$extension.order}" class="nxc-extensions-order" /></td>

					{* Name. *}
					<td class="nxc-extensions-name">
						<strong>{$extension.name|wash}</strong>
						{if gt( $extension.dependencies|count(), 0 )}
						<p>{'Depends on:'|i18n( 'design/admin/setup/extensions' )}</p>
						<ul class="dependencies">
							{foreach $extension.dependencies as $dependency_extension_name}
							<li class="{if $available_extensions[$dependency_extension_name].is_activated}activated-dependency{else}deactivated-dependency{/if}">{$dependency_extension_name|wash}</li>
							{/foreach}
						</ul>
						{/if}
					</td>

				</tr>
			{/foreach}
			{undef $can_be_activated}
			</tbody>

		</table>
		{else}
		<div class="block">
			<p>{'There are no available extensions.'|i18n( 'design/admin/setup/extensions' )}</p>
		</div>
		{/if}

		<script type="text/javascript">
		{literal}
		if( typeof( MooTools ) != 'undefined' ) {
			window.addEvent( 'domready', function() {
				var container = document.id( 'nxc-extensions-list' );
				container.getElements( 'td.nxc-extensions-name' ).each( function( el ) {
					el.setStyle( 'cursor', 'pointer' );
				} );

				container.getElements( 'td.nxc-extensions-order' ).extend(
					document.getElements( 'th.nxc-extensions-order' )
				).each( function( el ) {
					el.setStyle( 'display', 'none' );
				} );

				var extensionSortables = new Sortables( container, {
					constrain: false,
					clone: function( e, el, list ) {
						return el.clone().setStyles(
							$merge(
								{
									'opacity': '0.8',
									'border': '1px dashed #4D7299'
								},
								el.getCoordinates()
							)
						).inject( document.body );
					},
					revert: true,
					handle: 'td.nxc-extensions-name'
				} );
				extensionSortables.addEvent( 'complete', function() {
					var counter = 0;
					container.getChildren().each( function( el ) {
						el.removeClass( 'bgdark' ).removeClass( 'bglight' ).addClass( ( ( counter % 2 ) == 0 ) ? 'bgdark' : 'bglight' );
						el.getElement( 'input.nxc-extensions-order' ).set( 'value', counter );
						counter++;
					} );
				} );
			} );
		}
		{/literal}
		</script>
		{* DESIGN: Content END *}</div></div></div>

		<div class="controlbar">
			{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
				<div class="block">
					{if gt( $available_extensions|count(), 0 )}
					<input class="button" type="submit" name="ActivateExtensionsButton" value="{'Apply changes'|i18n( 'design/admin/setup/extensions' )}" title="{'Click this button to store changes if you have modified the status of the checkboxes above.'|i18n( 'design/admin/setup/extensions' )}" />
					{else}
					<input class="button-disabled" type="submit" name="ActivateExtensionsButton" value="{'Apply changes'|i18n( 'design/admin/setup/extensions' )}" disabled="disabled" />
					{/if}
					<input class="button" type="submit" name="GenerateAutoloadArraysButton" value="{'Regenerate autoload arrays for extensions'|i18n( 'design/admin/setup/extensions' )}" title="{'Click this button to regenerate the autoload arrays used by the system for extensions.'|i18n( 'design/admin/setup/extensions' )}" />
				</div>
			{* DESIGN: Control bar END *}</div></div></div></div></div></div>
		</div>

	</div>

</form>
