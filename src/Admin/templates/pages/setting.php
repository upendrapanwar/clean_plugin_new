<?php defined('ABSPATH') || exit; ?>

<?php $this->layout('admin::layout') ?>

<div class="wrapper-free mt-5 pb">

    <div class="pr-5">
        <form method="POST">
            <?= $wp_nonce_field ?>

            <h2 class="title">Oxygen Builder Patch</h2>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"> Oxygen Builder version </th>
                        <td>
                            <p>
                                <span class="font-bold"><?= $oxygen_builder_version ?> <?= $is_supported ? '' : '(Unsupported Version)' ?></span>
                            </p>
                            <p class="description">
                                Plain Classes support the following Oxygen Builder versions: <?= implode(', ', array_map(fn ($key) => '<code>' . $key . '</code>', array_keys($available_patch))) ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"> Patch version </th>
                        <td>
                            <p>
                                <?php if (!$is_supported) : ?>
                                    <span class="text-red-700">Unsupported Oxygen Builder version</span>
                                <?php elseif (!$patch_version) : ?>
                                    <span class="text-yellow-700">Unpatched</span>
                                <?php else : ?>
                                    <span class="font-bold"><?= $patch_version ?></span>
                                    <span class="font-normal text-white bg-green-700 px-1.5 py-1 rounded ml-2.5">Patched</span>
                                <?php endif; ?>
                            </p>
                            <?php if ($is_supported && (false === $patch_version || false !== $get_newer_patch_version)) : ?>
                                <p class="description">
                                    <a class="button button-secondary mt-2" href="<?= $patch_action_url ?>">Patch now</a>
                                    <?php if (false !== $get_newer_patch_version) : ?>
                                        <span class="text-yellow-700"> (New version available: <?= $get_newer_patch_version ?>) </span>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!$is_supported || !$patch_version || false !== $get_newer_patch_version) : ?>
                                <p class="description pt-2">
                                    <span class="text-yellow-700 font-medium">⚠️ Caution</span>: This patch will overwrite the original Oxygen Builder's file. If you have modified the original Oxygen Builder's file, the changes will be lost.
                                </p>
                            <?php endif; ?>

                        </td>
                    </tr>
                </tbody>
            </table>

            <h2 class="title">Migration</h2>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"> To Plain Classes </th>
                        <td>
                            <a class="button button-secondary" href="<?= $migrate_to_action_url ?>"> Migrate </a>
                            <p class="desciption">
                                <span class="text-yellow-700 font-medium">⚠️ Warning</span>: This will migrate your Oxygen Selector System to Plain Classes. All customized selectors will be untouched.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"> To Oxygen Selector System </th>
                        <td>
                            <a class="button button-secondary" href="<?= $migrate_from_action_url ?>"> Migrate </a>
                            <p class="desciption">
                                <span class="text-yellow-700 font-medium">⚠️ Warning</span>: This will migrate your Plain Classes to Oxygen Selector System.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2 class="title">Autocomplete Integration</h2>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"> Oxygen Selector System </th>
                        <td>
                            <fieldset>
                                <label for="enable_autocomplete_oxygen_selector">
                                    <input name="enable_autocomplete_oxygen_selector" id="enable_autocomplete_oxygen_selector" type="checkbox" value="1" <?= $enable_autocomplete_oxygen_selector ? 'checked' : '' ?>>
                                    Enable
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"> OxyMade Framework</th>
                        <td>
                            <?php if (defined('OXYMADE_PLUGIN_FILE')) : ?>
                                <fieldset>
                                    <label for="enable_autocomplete_oxymade">
                                        <input name="enable_autocomplete_oxymade" id="enable_autocomplete_oxymade" type="checkbox" value="1" <?= $enable_autocomplete_oxymade ? 'checked' : '' ?>>
                                        Enable
                                    </label>
                                </fieldset>
                            <?php else : ?>
                                <p class="desciption">
                                    <span class="text-yellow-900 bg-yellow-50 px-1.5 py-1 rounded border border-solid border-yellow-900/40 select-none">❌ Not Detected</span>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"> OxyNinja </th>
                        <td>
                            <?php if (defined('OXYNINJA_PLUGIN_FILE')) : ?>
                                <fieldset>
                                    <label for="enable_autocomplete_oxyninja">
                                        <input name="enable_autocomplete_oxyninja" id="enable_autocomplete_oxyninja" type="checkbox" value="1" <?= $enable_autocomplete_oxyninja ? 'checked' : '' ?>>
                                        Enable
                                    </label>
                                </fieldset>
                            <?php else : ?>
                                <p class="desciption">
                                    <span class="text-yellow-900 bg-yellow-50 px-1.5 py-1 rounded border border-solid border-yellow-900/40 select-none">❌ Not Detected</span>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"> Automatic.css </th>
                        <td>
                            <?php if (defined('ACSS_PLUGIN_FILE')) : ?>
                                <fieldset>
                                    <label for="enable_autocomplete_automaticcss">
                                        <input name="enable_autocomplete_automaticcss" id="enable_autocomplete_automaticcss" type="checkbox" value="1" <?= $enable_autocomplete_automaticcss ? 'checked' : '' ?>>
                                        Enable
                                    </label>
                                </fieldset>
                            <?php else : ?>
                                <p class="desciption">
                                    <span class="text-yellow-900 bg-yellow-50 px-1.5 py-1 rounded border border-solid border-yellow-900/40 select-none">❌ Not Detected</span>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            </p>
        </form>
    </div>

</div>