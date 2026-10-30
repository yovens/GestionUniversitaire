<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/** Sauvegarde compressée de la base de données (MySQL/MariaDB ou SQLite), avec rotation. */
class Sauvegarde extends Command
{
    protected $signature = 'itac:sauvegarde {--garder=14 : Nombre de sauvegardes à conserver}';

    protected $description = 'Sauvegarde la base de données dans storage/app/private/sauvegardes';

    public function handle(): int
    {
        $dir = storage_path('app/private/sauvegardes');
        File::ensureDirectoryExists($dir, 0750);
        $conn = config('database.default');
        $cfg = config("database.connections.$conn");
        $fichier = $dir.'/itac-'.now()->format('Ymd-His');

        if (in_array($cfg['driver'], ['mysql', 'mariadb'], true)) {
            $fichier .= '.sql.gz';
            $defaults = tempnam(sys_get_temp_dir(), 'itacdb');
            file_put_contents($defaults, "[client]\nuser=\"{$cfg['username']}\"\npassword=\"{$cfg['password']}\"\nhost=\"{$cfg['host']}\"\nport=\"{$cfg['port']}\"\n");
            chmod($defaults, 0600);
            $cmd = 'mysqldump --defaults-extra-file='.escapeshellarg($defaults).' --single-transaction --routines --no-tablespaces '
                .escapeshellarg($cfg['database']).' | gzip > '.escapeshellarg($fichier);
            $p = Process::fromShellCommandline('set -o pipefail; '.$cmd)->setTimeout(1800);
            $p->run();
            @unlink($defaults);
            if (! $p->isSuccessful()) {
                @unlink($fichier);
                $this->error('Échec de mysqldump : '.trim($p->getErrorOutput()));

                return self::FAILURE;
            }
        } elseif ($cfg['driver'] === 'sqlite') {
            $fichier .= '.sqlite.gz';
            file_put_contents($fichier, gzencode(file_get_contents($cfg['database']), 9));
        } else {
            $this->error("Pilote {$cfg['driver']} non pris en charge.");

            return self::FAILURE;
        }

        $tous = collect(File::files($dir))->sortByDesc(fn ($f) => $f->getMTime())->values();
        $tous->slice((int) $this->option('garder'))->each(fn ($f) => File::delete($f->getPathname()));

        $this->info('Sauvegarde créée : '.$fichier.' ('.round(filesize($fichier) / 1024).' Ko)');

        return self::SUCCESS;
    }
}
