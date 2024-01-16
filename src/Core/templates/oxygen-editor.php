<?php defined('ABSPATH') || exit; ?>

<div id="wakaloka-pc-toggle" class="wakaloka-pc-toggle" x-data @click="$store.visiblePanel = ! $store.visiblePanel" title="Plain Classes">
    <div :class="!$store.visiblePanel ? 'bg-transparent' : ''" class="wakaloka-pc-logo">
        ⚡
    </div>
</div>

<div id="wakaloka-pc-wrapper" class="wakaloka-pc-wrapper" x-data x-show="$store.visiblePanel" x-init="$watch('$store.plain_classes.data', $store.plainClassesWatcher)">
    <textarea id="wakaloka-pc-textarea" type="text" spellcheck="false" x-model="$store.plain_classes.data" placeholder="Write the classes" data-enable-grammarly="false"></textarea>
</div>

<?php do_action('a!Wakaloka\\PlainClasses\\Core\\Plain::before_tribute'); ?>

<script type="module" defer>
    import Tribute from 'https://cdn.skypack.dev/pin/tributejs@v5.1.3-FVCjiEtivWisraBgBDwo/mode=imports,min/optimized/tributejs.js';

    document.addEventListener("DOMContentLoaded", async () => {
        const tribute = new Tribute({
            // Limits the number of items in the menu
            menuItemLimit: 10,

            // turn tribute into an autocomplete
            autocompleteMode: true,

            // template for when no match is found (optional),
            // If no template is provided, menu is hidden.
            noMatchTemplate: '',

            // REQUIRED: array of objects to match or a function that returns data (see 'Loading remote data' for an example)
            // values: wakoloka_plain_classes_tribute.autocomplete,
            values: function(text, cb) {
                if (typeof provideAutocomplete === 'undefined') {
                    return cb(wakoloka_plain_classes_tribute.autocomplete);
                }

                provideAutocomplete(text, classes => cb(classes));
            },
        });

        tribute.attach(document.getElementById('wakaloka-pc-textarea'));
    });
</script>