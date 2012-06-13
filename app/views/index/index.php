<script>
    jQuery(document).ready(function() {
        jQuery('input[type=radio]').bind('change', function() {
            if (jQuery('input[type=radio][value=single]').is(':checked')) {
                jQuery('input[type=text][name=areaname]').attr('required', 'required');
            } else {
                console.log('removing required attr');
                jQuery('input[type=text][name=areaname]').removeAttr('required');
            }
        });
    });
    
    STUDIP.ForumPPMigrator = {
        searchVA: function() {
            jQuery('#search_results').load(
                '<?= PluginEngine::getLink('/forumppmigratorplugin/index/ajax_search/') ?>' +
                '&search_term=' + jQuery('input[name=searchfor]').val()
            );
        }
    }
</script>

<form method="post" action="<?= PluginEngine::getLink('/forumppmigratorplugin/index/migrate') ?>">
    <label>
        <input type="radio" name="area[]" value="single" checked="checked">
        <input type="text" name="areaname" placeholder="<?= _('Bereich, in den die Daten importiert werden') ?>" style="width: 300px;" required="required">
    </label>
    <br>

    <label>
        <input type="radio" name="area[]" value="multi">
        <?= _('Jeden Eintrag der obersten Ebene als eigenen Bereich anlegen') ?>
    </label>
    <br>
    
    <br>
    <?= _('Veranstaltung:') ?> <input type="text" name="searchfor" placeholder="Veranstaltungsname">
    <?= Studip\LinkButton::create(_('Suchen'), "javascript:STUDIP.ForumPPMigrator.searchVA()") ?><br>
    <span id="search_results">
        <?= $this->render_partial('index/ajax_search') ?>
    </span>
    <br><br>
    <?= Studip\Button::create(_('Daten aus dem Standardforum importieren')) ?>
</form>