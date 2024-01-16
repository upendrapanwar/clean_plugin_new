<?php defined('ABSPATH') || exit; ?>

<div class="inline-flex h-[4.5rem] items-center">
    <h1 class="pl-2">Plain Classes Settings <span class="text-[small] text-gray-500">v<?= $this->e($version) ?></span></h1>
</div>

<hr class="wp-header-end">
<h2 class="nav-tab-wrapper">
    <?php foreach ($tabs as $tab) : ?>
        <a href="<?= $this->e($tab['href']) ?>" class="nav-tab <?= $this->e($tab['classes']) ?>"><?= $this->e($tab['title']) ?></a>
    <?php endforeach ?>

    <a href="#" target="_blank" class="nav-tab inline-flex">
        Documentation
        <svg class="icon outbound" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" x="0px" y="0px" viewBox="0 0 100 100" width="15" height="15" data-v-641633f9="">
            <path fill="currentColor" d="M18.8,85.1h56l0,0c2.2,0,4-1.8,4-4v-32h-8v28h-48v-48h28v-8h-32l0,0c-2.2,0-4,1.8-4,4v56C14.8,83.3,16.6,85.1,18.8,85.1z"></path>
            <polygon fill="currentColor" points="45.7,48.7 51.3,54.3 77.2,28.5 77.2,37.2 85.2,37.2 85.2,14.9 62.8,14.9 62.8,22.9 71.5,22.9"></polygon>
        </svg>
    </a>
</h2>

<?= $this->section('content') ?>