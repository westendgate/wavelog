<?php $archive = !empty($hamdash_archive); ?>
<div class="ham-dash-shell">
    <nav class="ham-dash-nav" aria-label="<?= html_escape(__("Ham-Dash views")); ?>">
        <a class="btn <?= $archive ? 'btn-outline-primary' : 'btn-primary'; ?>" href="<?= html_escape(site_url('map/propagation')); ?>"<?= !$archive ? ' aria-current="page"' : ''; ?>>
            <i class="fas fa-broadcast-tower" aria-hidden="true"></i> <?= html_escape(__("Live Map")); ?>
        </a>
        <a class="btn <?= $archive ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?= html_escape(site_url('map/propagation_archive')); ?>"<?= $archive ? ' aria-current="page"' : ''; ?>>
            <i class="fas fa-calendar-alt" aria-hidden="true"></i> <?= html_escape(__("Propagation Archive")); ?>
        </a>
    </nav>
    <iframe
        class="ham-dash-frame"
        src="<?= html_escape(site_url('ham-dash/') . ($archive ? '?view=archive' : '')); ?>"
        title="<?= html_escape($archive ? __("Propagation Archive") : 'Ham-Dash'); ?>"
        loading="eager"
        referrerpolicy="same-origin"
    ></iframe>
</div>

<style>
.ham-dash-shell {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 56px);
    min-height: 620px;
    overflow: hidden;
}
.ham-dash-nav {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: .5rem 1rem;
    flex: 0 0 auto;
}
.ham-dash-frame {
    border: 0;
    display: block;
    flex: 1 1 auto;
    min-height: 0;
    width: 100%;
}
@media (max-width: 760px) {
    .ham-dash-shell {
        height: calc(100vh - 50px);
        min-height: 540px;
    }
}
</style>
