<?php
declare(strict_types=1);

/**
 * Keep user media and runtime-published plugin assets in separate namespaces.
 */
return new class {
    public function up(SQLite3 $db): void
    {
        $this->moveRegisteredPluginAssets(
            $db,
            APP_ROOT . '/uploads/plugins',
            APP_ROOT . '/assets/plugins'
        );
    }

    public function down(SQLite3 $db): void
    {
        $this->moveRegisteredPluginAssets(
            $db,
            APP_ROOT . '/assets/plugins',
            APP_ROOT . '/uploads/plugins'
        );
    }

    private function moveRegisteredPluginAssets(SQLite3 $db, string $sourceRoot, string $targetRoot): void
    {
        if (!is_dir($sourceRoot) || !$this->tableExists($db, 'plugins')) {
            return;
        }

        $result = $db->query('SELECT plugin_id FROM plugins');
        while ($result && ($row = $result->fetchArray(SQLITE3_ASSOC))) {
            $pluginId = trim((string)($row['plugin_id'] ?? ''));
            if (preg_match('/^[a-z0-9][a-z0-9._-]*$/Di', $pluginId) !== 1) {
                continue;
            }

            $source = $sourceRoot . '/' . $pluginId;
            if (!is_dir($source)) {
                continue;
            }

            $target = $targetRoot . '/' . $pluginId;
            $this->moveDirectory($source, $target);
        }

        @rmdir($sourceRoot);
    }

    private function moveDirectory(string $source, string $target): void
    {
        $targetParent = dirname($target);
        if (!is_dir($targetParent) && !mkdir($targetParent, 0755, true) && !is_dir($targetParent)) {
            throw new RuntimeException('Unable to create plugin asset directory: ' . $targetParent);
        }

        if (!file_exists($target) && @rename($source, $target)) {
            return;
        }

        if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
            throw new RuntimeException('Unable to create plugin asset directory: ' . $target);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isLink()) {
                throw new RuntimeException('Plugin asset directory must not contain symbolic links');
            }

            $destination = $target . '/' . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
                    throw new RuntimeException('Unable to create plugin asset directory: ' . $destination);
                }
                continue;
            }

            if (!copy($item->getPathname(), $destination)) {
                throw new RuntimeException('Unable to migrate plugin asset: ' . $item->getPathname());
            }
        }

        $this->removeDirectory($source);
    }

    private function removeDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($directory);
    }

    private function tableExists(SQLite3 $db, string $table): bool
    {
        $stmt = $db->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :table LIMIT 1");
        $stmt->bindValue(':table', $table, SQLITE3_TEXT);
        $result = $stmt->execute();

        return $result !== false && $result->fetchArray(SQLITE3_NUM) !== false;
    }
};
