<?php
	$selectedLang = empty( $params['wikiLanguage'] ) ? $wg->LanguageCode : $params['wikiLanguage'];
?>
<section id="CreateNewWiki">
	<h1><?= wfMessage( 'cnw-title' )->escaped() ?></h1>

	<ol class="steps">
		<li id="NameWiki" class="step">
			<h2><?= wfMessage( 'cnw-name-wiki-headline' )->escaped() ?></h2>
			<p class="creative"><?= wfMessage( 'cnw-name-wiki-creative' )->escaped() ?></p>

			<form name="label-wiki-form">
				<div class="wiki-name-container">
					<?=
					wfMessage('autocreatewiki-title-template')->rawParams(
						Xml::element('input', [
							'type' => 'text',
							'name' => 'wiki-name',
							'value' => $params['wikiName'],
						])
					)->escaped()
					?>
					<label class="floating-label" for="wiki-name">
						<?= wfMessage( 'cnw-name-wiki-label' )->escaped() ?>
					</label>
					<div class="wiki-name-error error-msg"></div>
				</div>
				<div class="wiki-domain-container">
					<span class="domain-country">
						<?= ( empty( $selectedLang ) || $selectedLang === 'en' ) ? '' : Sanitizer::escapeHtmlAllowEntities( $selectedLang ) . '.' ?>
					</span>
					<?= wfMessage( 'cnw-name-wiki-language' )->escaped() ?>
					<input type="text" name="wiki-domain" value="<?= Sanitizer::encodeAttribute( $params['wikiDomain'] ) ?>">
					.<?= htmlspecialchars( $wikiaBaseDomain ) ?>
					<label for="wiki-domain" class="floating-label"><?= wfMessage( 'cnw-name-wiki-domain-label' )->escaped() ?></label>
					<div class="wiki-domain-error error-msg"></div>
				</div>

				<div class="cnw-select">
					<h3><?= wfMessage( 'cnw-desc-lang' )->escaped() ?></h3>
					<div class="wds-dropdown">
						<div class="wds-dropdown__toggle">
							<? if ( !empty( $aLanguages ) && is_array( $aLanguages ) ) : ?>
								<span class="default-value">
									 <?= Sanitizer::escapeHtmlAllowEntities( $aLanguages[$selectedLang] ) ?>
								</span>
							<? endif ?>
							<?= DesignSystemHelper::renderSvg( 'wds-icons-dropdown-tiny' ); ?>
						</div>
						<div class="wds-dropdown__content wds-dropdown__wiki-language">
							<ul class="wds-list">
								<? if ( !empty( $aTopLanguages ) && is_array( $aTopLanguages ) ) : ?>
									<li class="spacer"><?= wfMessage( 'autocreatewiki-language-top', count( $aTopLanguages ) )->escaped() ?></li>
									<? foreach ( $aTopLanguages as $sLang ) : ?>
										<li id="<?= Sanitizer::encodeAttribute( $sLang ) ?>">
											<?= Sanitizer::escapeHtmlAllowEntities( $sLang ) ?>: <?= Sanitizer::escapeHtmlAllowEntities( $aLanguages[$sLang] ) ?>
										</li>
									<? endforeach ?>
								<? endif ?>

								<? if ( !empty( $aLanguages ) && is_array( $aLanguages ) ) : ?>
									<li class="spacer"><?= wfMessage( 'autocreatewiki-language-all' )->escaped() ?></li>
									<? ksort( $aLanguages );
									foreach ( $aLanguages as $sLang => $sLangName ) : ?>
										<li id="<?= Sanitizer::encodeAttribute( $sLang ) ?>">
											<?= Sanitizer::escapeHtmlAllowEntities( $sLang ) ?>: <?= Sanitizer::escapeHtmlAllowEntities( $sLangName ) ?>
										</li>
									<? endforeach ?>
								<? endif ?>
							</ul>
						</div>
					</div>
					<input type="hidden" name="wiki-language" value="<?= $selectedLang ?>">
				</div>

				<span class="submit-error error-msg"></span>
				<nav class="controls">
					<input type="button" value="<?= wfMessage( 'cnw-next' )->escaped() ?>" class="next">
				</nav>
			</form>
		</li>
		<li id="DescWiki" class="step">
			<h2><?= wfMessage( 'cnw-desc-headline' ) ?></h2>
			<p class="creative"><?= wfMessage( 'cnw-desc-creative' )->escaped() ?></p>
			<form name="desc-form" class="clearfix">
				<div class="desc-block">
					<textarea id="Description" placeholder="<?= wfMessage( 'cnw-desc-placeholder' )->escaped() ?>"></textarea>
					<div class="checkbox" id="all-ages-div"
						<?= (empty( $selectedLang ) || $selectedLang === $params['LangAllAgesOpt']) ? '' : 'style="display: none"' ?>>
						<input type="checkbox" name="all-ages" value="1">
						<?= $app->renderView(
							'WikiaStyleGuideTooltipIcon',
							'index',
							[
								'text' => wfMessage( 'cnw-desc-all-ages' )->escaped(),
								'tooltipIconTitle' => wfMessage( 'cnw-desc-tip-all-ages' )->plain(),
							]
						 );
						?>
					</div>
				</div>

				<!-- Hub Category / Vertical -->
				<div class="cnw-select">
					<h3><?= wfMessage( 'cnw-desc-select-vertical' )->escaped() ?></h3>
					<div class="wds-dropdown">
						<div class="wds-dropdown__toggle">
							<span class="default-value"><?= wfMessage( 'cnw-desc-select-one' )->escaped() ?></span>
							<?= DesignSystemHelper::renderSvg( 'wds-icons-dropdown-tiny' ); ?>
						</div>
						<div class="wds-dropdown__content wds-dropdown__wiki-vertical">
							<ul class="wds-list">
								<li id="-1"><?= wfMessage( 'cnw-desc-select-one' )->escaped() ?></li>
								<?php
									foreach ( $verticals as $vertical ) {
								?>
									<li
										id="<?= Sanitizer::encodeAttribute( $vertical['id'] ) ?>"
										data-short="<?= Sanitizer::encodeAttribute( $vertical['short'] ) ?>"
										data-categoriesset="<?= Sanitizer::encodeAttribute( $vertical['categoriesSet'] ) ?>">
										<?= Sanitizer::escapeHtmlAllowEntities( $vertical['name'] ) ?>
									</li>
								<?php
									}
								?>
							</ul>
						</div>
					</div>
					<input type="hidden" name="wiki-vertical" value="-1">
				</div>

				<!-- Additional Categories -->
				<div class="select-container categories-sets">
					<h3><?= wfMessage( 'cnw-desc-select-categories' )->escaped() ?></h3>
			<?php
				foreach ( $categoriesSets as $setId => $categoriesSet ) {
			?>

					<div class="categories-set" id="categories-set-<?= Sanitizer::encodeAttribute( $setId ) ?>">
				<?php
					foreach ( $categoriesSet as $category ) {
				?>
						<label>
							<input type="checkbox"
								value="<?= Sanitizer::encodeAttribute( $category['id'] ) ?>"
								data-short="<?= Sanitizer::encodeAttribute( $category['short'] ) ?>">
							<span><?= Sanitizer::escapeHtmlAllowEntities( $category['name'] ) ?></span>
						</label>
				<?php
					}
				?>
					</div>
			<?php
				}
			?>
				</div>

				<span class="submit-error error-msg"></span>
				<nav class="controls">
					<input type="button" value="<?= wfMessage( 'cnw-back' )->escaped() ?>" class="back">
					<input type="button" value="<?= wfMessage( 'cnw-next' )->escaped() ?>" class="next">
				</nav>
			</form>
		</li>
		<li id="ThemeWiki" class="step">
			<h2><?= wfMessage( 'cnw-theme-headline' )->escaped() ?></h2>
			<p class="creative"><?= wfMessage( 'cnw-theme-creative' )->escaped() ?></p>
			<?= F::app()->renderView( 'ThemeDesigner', 'ThemeTab' ) ?>
			<p class="instruction creative"><?= wfMessage( 'cnw-theme-instruction' )->escaped() ?></p>
			<span class="submit-error finish-status"></span>
			<nav class="controls">
				<input type="button" value="<?= wfMessage( 'cnw-next' )->escaped() ?>" class="next" disabled>
			</nav>
		</li>
	</ol>
</section>
<script>
	window.WikiBuilderCfg = <?= json_encode( $wikiBuilderCfg ) ?>;
	var themes = <?= json_encode( $wg->OasisThemes ) ?>;
	var applicationThemeSettings = <?= json_encode( $applicationThemeSettings ) ?>;
</script>
