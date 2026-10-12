<?php
$listing = $listing ?? [];
$summary = $summary ?? [];
$currentDir = $listing['directory'] ?? '';
$breadcrumbs = $listing['breadcrumbs'] ?? [];
$folders = $listing['folders'] ?? [];
$files = $listing['files'] ?? [];
$folderTree = $listing['folder_tree'] ?? [];
$currentStats = $listing['current_stats'] ?? ['folder_count' => 0, 'file_count' => 0, 'item_count' => 0, 'total_size_formatted' => '0 B'];
$filters = $filters ?? ['search' => '', 'type' => 'all', 'sort' => 'date_desc'];
$searchKeyword = (string)($filters['search'] ?? '');
$typeFilter = (string)($filters['type'] ?? 'all');
$sortFilter = (string)($filters['sort'] ?? 'date_desc');
$allowedSorts = ['date_desc', 'date_asc', 'name_asc', 'name_desc', 'size_asc', 'size_desc', 'type_asc', 'type_desc'];
if (!in_array($sortFilter, $allowedSorts, true)) {
    $sortFilter = 'date_desc';
}
$parentDir = $currentDir === '' ? '' : dirname($currentDir);
$parentDir = $parentDir === '.' ? '' : $parentDir;

$buildMediaUrl = static function (string $dir, ?string $sort = null) use ($searchKeyword, $typeFilter, $sortFilter): string {
    $effectiveSort = $sort ?? $sortFilter;
    $query = ['dir' => $dir];
    if ($searchKeyword !== '') {
        $query['search'] = $searchKeyword;
    }
    if ($typeFilter !== 'all') {
        $query['type'] = $typeFilter;
    }
    if ($effectiveSort !== 'date_desc') {
        $query['sort'] = $effectiveSort;
    }

    return url('/admin/media?' . http_build_query($query));
};

$getSortState = static function (string $field) use ($sortFilter): array {
    $ascending = $field . '_asc';
    $descending = $field . '_desc';
    $active = $sortFilter === $ascending || $sortFilter === $descending;
    $direction = $sortFilter === $ascending ? 'asc' : 'desc';

    $next = $active
        ? ($direction === 'asc' ? $descending : $ascending)
        : ($field === 'date' ? $descending : $ascending);

    return [
        'active' => $active,
        'direction' => $direction,
        'next' => $next,
        'next_direction' => $next === $ascending ? 'asc' : 'desc',
        'aria' => $active ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none',
    ];
};

$nameSort = $getSortState('name');
$dateSort = $getSortState('date');
$sizeSort = $getSortState('size');
$typeSort = $getSortState('type');

$renderTree = static function (array $nodes) use (&$renderTree, $buildMediaUrl): string {
    if ($nodes === []) {
        return '';
    }

    ob_start();
    echo '<ul class="explorer-tree-list">';
    foreach ($nodes as $node) {
        $classes = ['explorer-tree-node'];
        if (!empty($node['is_current'])) {
            $classes[] = 'is-current';
        }
        if (!empty($node['is_ancestor'])) {
            $classes[] = 'is-ancestor';
        }

        echo '<li class="' . h(implode(' ', $classes)) . '">';
        echo '<a href="' . h($buildMediaUrl((string)($node['directory'] ?? ''))) . '" class="explorer-tree-link">';
        echo '<span class="inline-flex h-5 w-5 items-center justify-center"><i class="fas fa-folder"></i></span>';
        echo '<span>' . h((string)($node['name'] ?? '')) . '</span>';
        echo '</a>';
        echo $renderTree((array)($node['children'] ?? []));
        echo '</li>';
    }
    echo '</ul>';

    return (string)ob_get_clean();
};
?>

<div class="page-header bg-indigo-600 shadow-[0_10px_40px_rgba(31,111,235,0.28)]">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="flex items-center gap-3 text-xl font-bold text-white sm:text-2xl">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-white/16 text-white">
                    <i class="fas fa-folder-open"></i>
                </span>
                <span>媒体资源管理器</span>
            </h1>
            <p class="mt-2 max-w-3xl text-sm text-white/80">你可以在这里管理和浏览你的媒体文件，包括图片、视频和其他文件类型。</p>
        </div>
        <div class="header-actions">
            <a href="<?= url('/admin/media') ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-sky-700 shadow-sm transition hover:bg-sky-50">
                <i class="fas fa-home text-xs"></i>
                <span>返回根目录</span>
            </a>
        </div>
    </div>
</div>

<div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3 mb-6">
    <div>
        <div class="card p-5">
            <div class="stat-mini">
                <div class="icon-box" style="background: var(--info-solid);">
                    <i class="fas fa-file-image"></i>
                </div>
                <div class="stat-info">
                    <div class="value"><?= number_format((int)($summary['image_count'] ?? 0)) ?></div>
                    <div class="label">图片数量</div>
                </div>
            </div>
        </div>
    </div>
    <div>
        <div class="card p-5">
            <div class="stat-mini">
                <div class="icon-box" style="background: var(--warning-solid)">
                    <i class="fas fa-video text-gray-800"></i>
                </div>
                <div class="stat-info">
                    <div class="value"><?= number_format((int)($summary['video_count'] ?? 0)) ?></div>
                    <div class="label">视频数量</div>
                </div>
            </div>
        </div>
    </div>
    <div>
        <div class="card p-5">
            <div class="stat-mini">
                <div class="icon-box" style="background: var(--success-solid);">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-info">
                    <div class="value"><?= h((string)($summary['total_size_formatted'] ?? '0 B')) ?></div>
                    <div class="label">总存储占用</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="flex items-start justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm mb-6">
        <span><?= h($_GET['success']) ?></span>
        <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-emerald-500 transition hover:bg-emerald-100" data-dismiss-parent>
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="flex items-start justify-between gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
        <span><?= h($_GET['error']) ?></span>
        <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-rose-500 transition hover:bg-rose-100" data-dismiss-parent>
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('/admin/media/delete') ?>" id="media-bulk-delete-form" class="hidden">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="dir" value="<?= h($currentDir) ?>">
</form>

<form method="post" action="<?= url('/admin/media/delete') ?>" id="single-media-delete-form" class="hidden">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="dir" value="<?= h($currentDir) ?>">
    <input type="hidden" name="path" id="single-media-delete-path" value="">
</form>

<form method="post" action="<?= url('/admin/media/folder/create') ?>" id="page-media-create-folder-form" class="hidden">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="dir" value="<?= h($currentDir) ?>">
    <input type="hidden" name="folder_name" id="page-media-create-folder-name" value="">
</form>

<form method="post" action="<?= url('/admin/media/upload') ?>" enctype="multipart/form-data" id="page-media-upload-form" class="hidden">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="dir" value="<?= h($currentDir) ?>">
    <input type="file" name="media_files[]" id="page-media-upload-input" multiple accept="image/*,video/mp4,video/webm,video/ogg,video/quicktime">
</form>

<form method="post" action="<?= url('/admin/media/folder/delete') ?>" id="page-media-folder-delete-form" class="hidden">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="directory" id="page-media-folder-delete-dir" value="">
    <input type="hidden" name="parent_dir" value="<?= h($currentDir) ?>">
</form>

<div id="page-media-preview-modal" class="page-media-modal fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="page-media-preview-heading" aria-hidden="true" tabindex="-1">
    <div class="flex max-h-[calc(100vh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-slate-900" id="page-media-preview-heading">预览 / 属性</h2>
                <p class="mt-1 truncate text-sm text-slate-500" id="page-media-preview-subtitle">查看媒体文件及其详细信息</p>
            </div>
            <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" data-media-preview-close aria-label="关闭预览">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="grid min-h-0 flex-1 overflow-y-auto lg:grid-cols-[minmax(0,1.15fr)_minmax(300px,0.85fr)]">
            <div class="border-b border-slate-200 bg-slate-50 p-4 sm:p-6 lg:border-b-0 lg:border-r">
                <div class="page-media-preview-box" id="page-media-preview-box"></div>
            </div>
            <div class="page-media-preview-meta p-5 sm:p-6">
                <div class="page-media-preview-row">
                    <span>标题</span>
                    <strong id="page-media-preview-title"></strong>
                </div>
                <div class="page-media-preview-row">
                    <span>原始文件名</span>
                    <strong id="page-media-preview-name"></strong>
                </div>
                <div class="page-media-preview-row">
                    <span>存储文件名</span>
                    <strong id="page-media-preview-storage"></strong>
                </div>
                <div class="page-media-preview-row">
                    <span>类型</span>
                    <strong id="page-media-preview-type"></strong>
                </div>
                <div class="page-media-preview-row">
                    <span>所在目录</span>
                    <strong id="page-media-preview-directory"></strong>
                </div>
                <div class="page-media-preview-row">
                    <span>大小 / 尺寸</span>
                    <strong id="page-media-preview-size"></strong>
                </div>
                <div class="page-media-preview-row">
                    <span>修改时间</span>
                    <strong id="page-media-preview-date"></strong>
                </div>
                <div class="space-y-2 border-t border-slate-100 pt-4">
                    <label class="text-xs font-semibold text-slate-500" for="page-media-preview-path">文件路径</label>
                    <div class="flex gap-2">
                        <input class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 outline-none" id="page-media-preview-path" type="text" readonly>
                        <button type="button" class="inline-flex items-center justify-center rounded-lg bg-sky-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-sky-600" data-copy-target="#page-media-preview-path">复制</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-200 px-5 py-3 sm:px-6">
            <button type="button" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50" data-media-preview-close>关闭</button>
        </div>
    </div>
</div>

<div id="page-media-edit-modal" class="page-media-modal fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4">
    <div class="w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">编辑媒体信息</h2>
                <p class="mt-1 text-sm text-slate-500">可修改媒体库展示用的标题和原始文件名，不会改动服务器上的物理文件。</p>
            </div>
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" data-media-edit-close aria-label="关闭">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="post" action="<?= url('/admin/media/update') ?>" class="space-y-5 px-6 py-6">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="dir" value="<?= h($currentDir) ?>">
            <input type="hidden" name="path" id="page-media-edit-path" value="">

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="page-media-edit-original-name" class="mb-2 block text-sm font-medium text-slate-700">原始文件名</label>
                    <input type="text" id="page-media-edit-original-name" name="original_name" maxlength="180" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100">
                    <p class="mt-2 text-xs leading-5 text-slate-400">用于后台展示，不能为空，不会重命名 /uploads 目录里的真实文件。</p>
                </div>
                <div>
                    <label for="page-media-edit-title" class="mb-2 block text-sm font-medium text-slate-700">标题</label>
                    <input type="text" id="page-media-edit-title" name="title" maxlength="180" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100">
                    <p class="mt-2 text-xs leading-5 text-slate-400">可选，用于后台和媒体选择器展示；留空时仅显示原始文件名。</p>
                </div>
            </div>

            <div class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 md:grid-cols-2">
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">存储文件名</span>
                    <strong class="mt-1 block break-all text-slate-700" id="page-media-edit-storage-name">-</strong>
                </div>
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">文件路径</span>
                    <strong class="mt-1 block break-all text-slate-700" id="page-media-edit-public-path">-</strong>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-5">
                <button type="button" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50" data-media-edit-close>取消</button>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-sky-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-600">保存修改</button>
            </div>
        </form>
    </div>
</div>

<div class="card explorer-shell">
    <div class="explorer-topbar">
        <div class="explorer-topbar-left">
            <span class="inline-flex h-5 w-5 items-center justify-center explorer-app-icon"><i class="fas fa-photo-video"></i></span>
            <strong class="explorer-app-title">媒体库</strong>
            <div class="explorer-nav-buttons flex items-center gap-2">
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50" data-history-nav="back">
                    <i class="fas fa-arrow-left text-xs"></i>
                </button>
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50" data-history-nav="forward">
                    <i class="fas fa-arrow-right text-xs"></i>
                </button>
                <a href="<?= h($buildMediaUrl($parentDir)) ?>" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
                    <i class="fas fa-level-up-alt text-xs"></i>
                </a>
            </div>
        </div>
        <div class="explorer-breadcrumbs">
            <nav aria-label="breadcrumbs">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                        <li class="flex items-center gap-2">
                            <?php if ($index === count($breadcrumbs) - 1): ?>
                                <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-medium text-slate-700" aria-current="page"><?= h($crumb['name']) ?></span>
                            <?php else: ?>
                                <a href="<?= h($buildMediaUrl((string)$crumb['directory'])) ?>" class="rounded-lg px-2.5 py-1 transition hover:bg-slate-100 hover:text-slate-700"><?= h($crumb['name']) ?></a>
                                <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>
    </div>

    <div class="explorer-toolbar">
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-sky-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-600" id="page-media-upload-btn">
                <i class="fas fa-upload text-xs"></i>
                <span>上传文件</span>
            </button>
            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50" id="page-media-new-folder-btn">
                <i class="fas fa-folder-plus text-xs"></i>
                <span>新建文件夹</span>
            </button>
            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50" id="page-media-delete-btn" disabled>
                <i class="fas fa-trash text-xs"></i>
                <span id="page-media-delete-label">删除</span>
            </button>
            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-400" disabled>
                <i class="fas fa-i-cursor text-xs"></i>
                <span>重命名</span>
            </button>
            <a href="<?= h($buildMediaUrl($currentDir)) ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                <i class="fas fa-sync-alt text-xs"></i>
                <span>刷新</span>
            </a>
        </div>

        <form method="get" action="<?= url('/admin/media') ?>" class="explorer-filter-bar">
            <input type="hidden" name="dir" value="<?= h($currentDir) ?>">
            <input type="hidden" name="sort" value="<?= h($sortFilter) ?>">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="relative flex-1">
                    <i class="fas fa-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    <input class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 focus:border-sky-400 focus:ring-2 focus:ring-sky-100" type="text" name="search" value="<?= h($searchKeyword) ?>" placeholder="搜索当前目录或原始文件名">
                </div>
                <select name="type" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100">
                    <option value="all" <?= $typeFilter === 'all' ? 'selected' : '' ?>>全部</option>
                    <option value="image" <?= $typeFilter === 'image' ? 'selected' : '' ?>>图片</option>
                    <option value="video" <?= $typeFilter === 'video' ? 'selected' : '' ?>>视频</option>
                </select>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100">搜索</button>
            </div>
        </form>
    </div>

    <div class="explorer-main">
        <aside class="explorer-sidebar">
            <div class="explorer-tree">
                <a href="<?= h($buildMediaUrl('')) ?>" class="explorer-tree-root <?= $currentDir === '' ? 'is-current' : '' ?>">
                    <span class="inline-flex h-5 w-5 items-center justify-center"><i class="fas fa-hdd"></i></span>
                    <span>我的媒体</span>
                </a>
                <?= $renderTree($folderTree) ?>
            </div>
        </aside>

        <section class="explorer-content">
            <div class="explorer-location-bar">
                <span class="inline-flex h-5 w-5 items-center justify-center"><i class="fas fa-map-marker-alt"></i></span>
                <span>当前位置：<?= h($listing['directory_display'] ?? '/uploads') ?></span>
            </div>

            <?php if (!empty($folders)): ?>
                <div class="explorer-folder-strip">
                    <?php foreach ($folders as $folder): ?>
                        <div class="explorer-folder-chip-wrap inline-flex items-center gap-0">
                            <a href="<?= h($buildMediaUrl((string)$folder['directory'])) ?>" class="explorer-folder-chip rounded-r-none mr-0">
                                <span class="inline-flex h-5 w-5 items-center justify-center"><i class="fas fa-folder"></i></span>
                                <span><?= h($folder['name']) ?></span>
                                <small><?= (int)$folder['item_count'] ?> 项</small>
                            </a>
                            <button type="button" class="js-folder-delete-btn inline-flex h-full items-center justify-center rounded-r-xl border border-l-0 border-slate-200 bg-white px-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-500" data-folder-dir="<?= h((string)$folder['directory']) ?>" data-folder-name="<?= h($folder['name']) ?>" data-folder-count="<?= (int)$folder['item_count'] ?>" title="删除文件夹">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="explorer-file-header" role="row">
                <label class="explorer-check-cell">
                    <input type="checkbox" id="page-media-toggle-all">
                </label>
                <div role="columnheader" aria-sort="<?= $nameSort['aria'] ?>">
                    <a class="explorer-sort-button <?= $nameSort['active'] ? 'is-active' : '' ?>" href="<?= h($buildMediaUrl($currentDir, $nameSort['next'])) ?>" title="按名称<?= $nameSort['next_direction'] === 'asc' ? '升序' : '降序' ?>排列">
                        <span>名称</span>
                        <i class="fas <?= $nameSort['active'] ? ($nameSort['direction'] === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down') : 'fa-sort' ?> explorer-sort-indicator" aria-hidden="true"></i>
                    </a>
                </div>
                <div role="columnheader" aria-sort="<?= $dateSort['aria'] ?>">
                    <a class="explorer-sort-button <?= $dateSort['active'] ? 'is-active' : '' ?>" href="<?= h($buildMediaUrl($currentDir, $dateSort['next'])) ?>" title="按修改时间<?= $dateSort['next_direction'] === 'asc' ? '升序' : '降序' ?>排列">
                        <span>修改时间</span>
                        <i class="fas <?= $dateSort['active'] ? ($dateSort['direction'] === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down') : 'fa-sort' ?> explorer-sort-indicator" aria-hidden="true"></i>
                    </a>
                </div>
                <div role="columnheader" aria-sort="<?= $sizeSort['aria'] ?>">
                    <a class="explorer-sort-button <?= $sizeSort['active'] ? 'is-active' : '' ?>" href="<?= h($buildMediaUrl($currentDir, $sizeSort['next'])) ?>" title="按大小<?= $sizeSort['next_direction'] === 'asc' ? '升序' : '降序' ?>排列">
                        <span>大小</span>
                        <i class="fas <?= $sizeSort['active'] ? ($sizeSort['direction'] === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down') : 'fa-sort' ?> explorer-sort-indicator" aria-hidden="true"></i>
                    </a>
                </div>
                <div role="columnheader" aria-sort="<?= $typeSort['aria'] ?>">
                    <a class="explorer-sort-button <?= $typeSort['active'] ? 'is-active' : '' ?>" href="<?= h($buildMediaUrl($currentDir, $typeSort['next'])) ?>" title="按类型<?= $typeSort['next_direction'] === 'asc' ? '升序' : '降序' ?>排列">
                        <span>类型</span>
                        <i class="fas <?= $typeSort['active'] ? ($typeSort['direction'] === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down') : 'fa-sort' ?> explorer-sort-indicator" aria-hidden="true"></i>
                    </a>
                </div>
                <div role="columnheader">操作</div>
            </div>

            <div class="explorer-file-list" id="page-media-file-list">
                <?php if (empty($files)): ?>
                    <div class="explorer-empty-state">
                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><i class="fas fa-folder-open text-xl"></i></span>
                        <p class="mt-4 text-sm">当前目录下没有符合筛选条件的媒体文件。</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($files as $file): ?>
                    <div
                        class="explorer-file-row"
                        data-file-path="<?= h($file['public_path']) ?>"
                        data-explorer-file='<?= h(json_encode([
                                                        'name' => $file['original_name'] ?: $file['name'],
                                                        'title' => $file['title'] ?? '',
                                                        'original_name' => $file['original_name'] ?: $file['name'],
                                                        'path' => $file['public_path'],
                                                        'url' => asset_url($file['public_path']),
                                                        'type' => $file['type'],
                                                        'size' => $file['size_formatted'],
                                                        'date' => $file['date'],
                                                        'dimensions' => $file['dimensions'] ?? '',
                                                        'storage_name' => $file['storage_name'] ?? '',
                                                        'directory' => $file['directory'] ?? '',
                                                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
                        <label class="explorer-check-cell" >
                            <input type="checkbox" class="js-page-media-checkbox" name="paths[]" form="media-bulk-delete-form" value="<?= h($file['public_path']) ?>">
                        </label>
                        <div class="explorer-name-cell">
                            <div class="explorer-thumb">
                                <?php if ($file['is_image']): ?>
                                    <img src="<?= asset_url($file['public_path']) ?>" alt="<?= h($file['original_name'] ?: $file['name']) ?>" loading="lazy">
                                <?php elseif ($file['is_video']): ?>
                                    <span class="inline-flex h-5 w-5 items-center justify-center"><i class="fas fa-video"></i></span>
                                <?php else: ?>
                                    <span class="inline-flex h-5 w-5 items-center justify-center"><i class="fas fa-file"></i></span>
                                <?php endif; ?>
                            </div>
                            <div class="explorer-name-meta">
                                <strong title="<?= h($file['original_name'] ?: $file['name']) ?>"><?= h($file['original_name'] ?: $file['name']) ?></strong>
                                <span>标题：<?= h(((string)($file['title'] ?? '')) !== '' ? (string)$file['title'] : '未设置') ?> · 存储名：<?= h($file['storage_name'] ?? $file['name']) ?></span>
                            </div>
                        </div>
                        <div><?= h($file['date']) ?></div>
                        <div><?= h($file['size_formatted']) ?></div>
                        <div><?= $file['is_video'] ? '视频' : ($file['is_image'] ? '图片' : '文件') ?></div>
                        <div class="explorer-actions" >
                            <button type="button" class="js-page-media-preview-btn inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700" title="预览与属性" aria-label="预览与属性">
                                <i class="fas fa-eye text-xs"></i>
                            </button>
                            <button
                                type="button"
                                class="js-page-media-edit-btn inline-flex h-9 w-9 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-600 transition hover:bg-sky-100 hover:text-sky-700"
                                data-edit-path="<?= h($file['public_path']) ?>"
                                data-edit-title="<?= h((string)($file['title'] ?? '')) ?>"
                                data-edit-original-name="<?= h($file['original_name'] ?: $file['name']) ?>"
                                data-edit-storage-name="<?= h($file['storage_name'] ?? $file['name']) ?>"
                                title="编辑文件信息">
                                <i class="fas fa-pen text-xs"></i>
                            </button>
                            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700" data-copy-path="<?= h($file['public_path']) ?>">
                                <i class="fas fa-copy text-xs"></i>
                            </button>
                            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-500 transition hover:bg-rose-100 hover:text-rose-600" data-delete-path="<?= h($file['public_path']) ?>">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </div>

    <div class="explorer-statusbar">
        <span id="page-media-selected-count">已选择 0 个文件</span>
        <span>当前目录 <?= (int)($currentStats['item_count'] ?? 0) ?> 个项目</span>
        <span>图片 <?= (int)($currentStats['file_count'] ?? 0) ?> 个文件</span>
        <span>总大小 <?= h((string)($currentStats['total_size_formatted'] ?? '0 B')) ?></span>
    </div>
</div>
