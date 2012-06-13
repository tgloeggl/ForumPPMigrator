<select name="seminar_id">
    <option value="<?= $seminar_id ?>"><?= _('Aktuelle Veranstaltung') ?></option>
    <? if (!empty($seminars)) foreach ($seminars as $seminar) : ?>
    <option value="<?= $seminar['Seminar_id'] ?>"><?= $seminar['Name'] ?></option>
    <? endforeach ?>
</select>
