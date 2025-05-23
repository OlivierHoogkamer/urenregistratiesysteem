<header class="topbar d-flex align-items-center">
    <div class="header-left d-flex align-items-center">
        <h5 class="mb-0">Logo en titel</h5>

        <?php if (isset($breadcrumb) && is_array($breadcrumb) && count($breadcrumb) > 0): ?>
            <nav aria-label="breadcrumb" class="breadcrumb-container ms-4 mb-0">
                <ol class="breadcrumb mb-0">
                    <?php foreach ($breadcrumb as $index => $item):
                        $isLast = $index === array_key_last($breadcrumb);
                        $label = htmlspecialchars($item['label']);
                        $url = $item['url'] ?? null;
                    ?>
                        <li class="breadcrumb-item <?= $isLast ? 'active' : '' ?>" <?= $isLast ? 'aria-current="page"' : '' ?>>
                            <?php if (!$isLast && $url): ?>
                                <a href="<?= htmlspecialchars($url) ?>"><?= $label ?></a>
                            <?php else: ?>
                                <?= $label ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Je zou hier eventueel rechts nog iets kunnen toevoegen -->
</header>
