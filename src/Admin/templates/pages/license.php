<?php defined('ABSPATH') || exit; ?>

<?php $this->layout('admin::layout') ?>

<form method="POST">
    <?= $wp_nonce_field ?>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label>License Key</label></th>
                <td>
                    <input class="min-w-[25rem]" name="license_key" type="password" value="<?= $this->e(esc_attr($license_key)) ?>" />
                    <?php if ($is_license_activated) : ?>
                        <div class="flex my-2.5 items-center font-medium">
                            Status: <span class="font-normal text-white bg-green-700 px-1.5 py-1 rounded ml-2.5">active</span>
                        </div>
                    <?php endif ?>
                    <p class="description">Enter your <a href="https://dplugins.com/products/wakaloka-plain-classes" target="_blank">license key</a> receive the update of the latest version.</p>

                </td>
            </tr>
            <tr>
                <th scope="row"><label>Pre-release version</label></th>
                <td>
                    <input id="pre-release" name="opt_in_beta" type="checkbox" value="1" <?= $this->e($opt_in_beta ? 'checked' : '') ?>>
                    <label for="pre-release"> Enable</label>
                    <p class="description">Opt in to get the pre-release version update. <span class="text-red-700">Pre-release version may unstable.</span></p>
                </td>
            </tr>
        </tbody>
    </table>

    <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
    </p>
</form>