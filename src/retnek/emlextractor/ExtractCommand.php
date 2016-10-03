<?php

namespace retnek\emlextractor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExtractCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('app:extract')
            ->setDescription('Extract eml files into array format.')
            ->addArgument('emlfolder', InputArgument::REQUIRED, 'The folder relative path contains of the eml files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $path = $input->getArgument('emlfolder');
        $path = getcwd() . "/$path";

        $directory = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
        $iterator->rewind();

        $all = iterator_count($iterator);

        while ($iterator->valid()) {
            if ($iterator->isDot() || !$iterator->isDir()) {
                $all--;
            }
        }

        $iterator->rewind();
        $emails = [];

        $io->progressStart($all);

        while ($iterator->valid()) {
            if (!$iterator->isDot() && !$iterator->isDir()) {
                $content = file_get_contents($iterator->key());
                $emails = array_merge($emails, $this->process($content));

                $io->progressAdvance();
            }

            $iterator->next();
        }

        $io->progressFinish();

        $csv = '';
        foreach ($emails as $email) {
            $csv .= $email . "\n";
        }

        file_put_contents(getcwd() . '/output/emailaddresses.csv', $csv);

        $io->success('Extracting done');
    }

    protected function process($content)
    {
        $rows = explode("\n", $content);
        $emails = [];
        foreach ($rows as $row) {
            if (substr(strtolower($row), 0, 3) == 'to:') {
                $email = substr($row, 4);
                preg_match_all('/[a-z\d._%+-]+@[a-z\d.-]+\.[a-z]{2,4}\b/i', $email, $matches);
                $emails = array_merge($emails, $matches[0]);
            }
        }

        return $emails;
    }
}