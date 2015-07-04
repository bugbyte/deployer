<?php

namespace Bugbyte\Deployer\Deploy;

use Bugbyte\Deployer\Exception\DeployException;

/**
 * The deployer
 */
class Deployer
{
    /**
     * If the deployer is run in debugging mode (more verbose output)
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Formatting of the deployment directories
     *
     * @var string
     */
    protected $remote_dir_format = '%project_name%_%timestamp%';

    /**
     * Date format in the name of the deployment directories
     * (format parameter of date())
     *
     * @var string
     */
    protected $remote_dir_timestamp_format = 'Y-m-d_His';

    /**
     * The codename of the application
     *
     * @var string
     */
    protected $project_name = null;

    /**
     * The root directory of the project
     *
     * @var string
     */
    protected $basedir = null;

    /**
     * The hostname(s) of the remote server(s)
     *
     * @var string|array
     */
    protected $remote_host = null;

    /**
     * The ssh port of the remote server(s)
     *
     * @var integer
     */
    protected $remote_port = 22;

    /**
     * The username of the account on the remote server
     *
     * @var string
     */
    protected $remote_user = null;

    /**
     * The directory of this project on the remote server
     *
     * @var string
     */
    protected $remote_dir = null;

    /**
     * All files to be used as rsync exclude
     *
     * @var array
     */
    protected $rsync_excludes = array();

    /**
     * The general timestamp of this deployment
     *
     * @var integer
     */
    protected $timestamp = null;

    /**
     * The directory where the new deployment will go
     *
     * @var string
     */
    protected $remote_target_dir = null;

    /**
     * The timestamp of the previous deployment
     *
     * @var integer
     */
    protected $previous_timestamp = null;

    /**
     * The timestamp of the latest deployment
     *
     * @var integer
     */
    protected $last_timestamp = null;

    /**
     * De directory van de voorlaatste deployment
     *
     * @var string
     */
    protected $previous_remote_target_dir = null;

    /**
     * De directory van de laatste deployment
     *
     * @var string
     */
    protected $last_remote_target_dir = null;

    /**
     * Het pad van de logfile, als logging gewenst is
     *
     * @var string
     */
    protected $logfile = null;

    /**
     * Doellocatie (stage of prod)
     *
     * @var string
     */
    protected $target = null;

    /**
     * Het pad van de datadir symlinker, relatief vanaf de project root
     *
     * @var string
     */
    protected $datadir_patcher = null;

    /**
     * Het pad van de gearman restarter, relatief vanaf de project root
     *
     * @var string
     */
    protected $gearman_restarter = null;

    /**
     * Directories waarin de site zelf dingen kan schrijven
     *
     * @var array
     */
    protected $data_dirs = null;

    /**
     * De naam van de directory waarin alle data_dirs worden geplaatst
     *
     * @var string
     */
    protected $data_dir_prefix = 'data';

    /**
     * deployment timestamps ophalen als deploy geïnstantieerd wordt
     *
     * @var boolean
     */
    protected $auto_init = true;

    /**
     * Files die specifiek zijn per omgeving
     *
     * voorbeeld:
     *         'config/databases.yml'
     *
     * bij publicatie naar stage gebeurd dit:
     *         'config/databases.stage.yml' => 'config/databases.yml'
     *
     * bij publicatie naar prod gebeurd dit:
     *         'config/databases.prod.yml' => 'config/databases.yml'
     *
     * @var array
     */
    protected $target_specific_files = array();

    /**
     * settings van gearman inclusief worker functies die herstart moeten worden
     * na deployment
     *
     * voorbeeld:
     *     array(
     *         'servers' => array(
     *             array('ip' => ipadres, 'port' => gearmanport)
     *         ),
     *         'workers' => array(
     *             'functienaam1',
     *             'functienaam2',
     *         )
     *     )
     *
     * @var array
     */
    protected $gearman = array();

    /**
     * Cache for listFilesToRename()
     *
     * @var array
     */
    protected $files_to_rename = array();

    /**
     * Command paths
     */
    protected $rsync_path = 'rsync';
    protected $ssh_path = 'ssh';

    /**
     * Bouwt een nieuwe Deploy class op met de gegeven opties
     *
     * @param array $options
     * @throws DeployException
     */
    public function __construct(array $options)
    {
        $this->project_name = $options['project_name'];
        $this->basedir = $options['basedir'];
        $this->remote_host = $options['remote_host'];
        $this->remote_user = $options['remote_user'];
        $this->target = $options['target'];
        $this->remote_dir = $options['remote_dir'] .'/'. $this->target;

        if (isset($options['remote_port']))
            $this->remote_port = $options['remote_port'];

        if (isset($options['rsync_excludes']))
            $this->rsync_excludes = (array) $options['rsync_excludes'];

        if (isset($options['logfile']))
            $this->logfile = $options['logfile'];

        if (isset($options['data_dirs']))
            $this->data_dirs = $options['data_dirs'];

        if (isset($options['datadir_patcher']))
            $this->datadir_patcher = $options['datadir_patcher'];

        if (isset($options['gearman_restarter']))
            $this->gearman_restarter = $options['gearman_restarter'];

        if (isset($options['auto_init']))
            $this->auto_init = $options['auto_init'];

        if (isset($options['target_specific_files']))
            $this->target_specific_files = $options['target_specific_files'];

        if (isset($options['gearman']))
            $this->gearman = $options['gearman'];

        $this->rsync_path = isset($options['rsync_path']) ? $options['rsync_path'] : trim(`which rsync`);
        $this->ssh_path = isset($options['ssh_path']) ? $options['ssh_path'] : trim(`which ssh`);

        if (!$this->auto_init)
            return;

        $this->initialize();
    }

    /**
     * Determines the timestamp of the new deployment and those of the latest two
     */
    protected function initialize()
    {
        $this->log('initialisatie', LOG_DEBUG);

        // in case of multiple remote hosts use the first
        $remote_host = is_array($this->remote_host) ? $this->remote_host[0] : $this->remote_host;

        $this->timestamp = time();
        $this->remote_target_dir = strtr($this->remote_dir_format, array(
                                        '%project_name%' => $this->project_name,
                                        '%timestamp%' => date($this->remote_dir_timestamp_format, $this->timestamp))
                                   );

        if ($timestamps = $this->findPastDeploymentTimestamps($remote_host, $this->remote_dir)) {
            list($this->previous_timestamp, $this->last_timestamp) = $timestamps;
        }

        if ($this->previous_timestamp) {
            $this->previous_remote_target_dir = strtr($this->remote_dir_format, array(
                                                    '%project_name%' => $this->project_name,
                                                    '%timestamp%' => date($this->remote_dir_timestamp_format, $this->previous_timestamp))
                                                );
        }

        if ($this->last_timestamp) {
            $this->last_remote_target_dir = strtr($this->remote_dir_format, array(
                                                    '%project_name%' => $this->project_name,
                                                    '%timestamp%' => date($this->remote_dir_timestamp_format, $this->last_timestamp))
                                            );
        }
    }

    /**
     * Run a dry-run to the remote server to show the changes to be made
     *
     * @param string $action		 update or rollback
     * @throws DeployException
     * @return bool				 if the user wants to proceed with the deployment
     */
    protected function check($action)
    {
        $this->log('check', LOG_DEBUG);

        if (is_array($this->remote_host)) {
            foreach ($this->remote_host as $key => $remote_host) {
                if ($key == 0) {
                    continue;
                }

                $this->prepareRemoteDirectory($remote_host, $this->remote_dir);
            }
        }

        if ($action == 'update') {
            $this->checkFiles(is_array($this->remote_host) ? $this->remote_host[0] : $this->remote_host, $this->remote_dir, $this->last_remote_target_dir);
        }

        if ($action == 'update') {
            if (is_array($this->remote_host)) {
                foreach ($this->remote_host as $remote_host) {
                    if ($files = $this->listFilesToRename($remote_host, $this->remote_dir)) {
                        $this->log("Target-specific file renames on $remote_host:");

                        foreach ($files as $filepath => $newpath) {
                            $this->log("  $newpath => $filepath");
                        }
                    }
                }
            } else {
                if ($files = $this->listFilesToRename($this->remote_host, $this->remote_dir)) {
                    $this->log('Target-specific file renames:');

                    foreach ($files as $filepath => $newpath) {
                        $this->log("  $newpath => $filepath");
                    }
                }
            }
        }

        // als alles goed is gegaan kan er doorgegaan worden met de deployment
        if ($action == 'update') {
            return static::inputPrompt('Proceed with deployment? (yes/no): ', 'no') == 'yes';
        }

        if ($action == 'rollback') {
            return static::inputPrompt('Proceed with rollback? (yes/no): ', 'no') == 'yes';
        }

        return false;
    }

    /**
     * Zet het project online en voert database-aanpassingen uit
     * Kan alleen worden uitgevoerd nadat check() heeft gedraaid
     */
    public function deploy()
    {
        $this->log('deploy', LOG_DEBUG);

        if (!$this->check('update')) {
            return;
        }

        if (is_array($this->remote_host)) {
            // eerst preDeploy draaien per host, dan alle files synchen
            foreach ($this->remote_host as $remote_host) {
                $this->preDeploy($remote_host, $this->remote_dir, $this->remote_target_dir);
                $this->updateFiles($remote_host, $this->remote_dir, $this->remote_target_dir);
            }

            $this->preActivation($this->remote_host[0], $this->remote_dir, $this->remote_target_dir);

            // als de files en database klaarstaan kan de nieuwe versie geactiveerd worden
            // door de symlinks te updaten en postDeploy te draaien
            foreach ($this->remote_host as $remote_host) {
                $this->changeSymlink($remote_host, $this->remote_dir, $this->remote_target_dir);
                $this->postDeploy($remote_host, $this->remote_dir, $this->remote_target_dir);
                $this->clearRemoteCaches($remote_host, $this->remote_dir, $this->remote_target_dir);
            }
        } else {
            $this->preDeploy($this->remote_host, $this->remote_dir, $this->remote_target_dir);
            $this->updateFiles($this->remote_host, $this->remote_dir, $this->remote_target_dir);

            $this->preActivation($this->remote_host, $this->remote_dir, $this->remote_target_dir);

            $this->changeSymlink($this->remote_host, $this->remote_dir, $this->remote_target_dir);
            $this->postDeploy($this->remote_host, $this->remote_dir, $this->remote_target_dir);
            $this->clearRemoteCaches($this->remote_host, $this->remote_dir, $this->remote_target_dir);
        }

        $this->cleanup();
    }

    /**
     * Draait de laatste deployment terug
     */
    public function rollback()
    {
        $this->log('rollback', LOG_DEBUG);

        if (!$this->previous_remote_target_dir) {
            $this->log('Rollback impossible, no previous deployment found !');
            return;
        }

        if (!$this->check('rollback')) {
            return;
        }

        if (is_array($this->remote_host)) {
            // eerst op alle hosts de symlink terugdraaien
            foreach ($this->remote_host as $remote_host) {
                $this->preRollback($remote_host, $this->remote_dir, $this->previous_remote_target_dir);
                $this->changeSymlink($remote_host, $this->remote_dir, $this->previous_remote_target_dir);
            }

            $this->postDeactivation($this->remote_host[0], $this->remote_dir, $this->previous_remote_target_dir);

            // de caches resetten
            foreach ($this->remote_host as $remote_host) {
                $this->clearRemoteCaches($remote_host, $this->remote_dir, $this->previous_remote_target_dir);
                $this->postRollback($remote_host, $this->remote_dir, $this->previous_remote_target_dir);
            }

            // als laatste de nieuwe directory terugdraaien
            foreach ($this->remote_host as $remote_host) {
                $this->rollbackFiles($remote_host, $this->remote_dir, $this->last_remote_target_dir);
            }
        } else {
            $this->preRollback($this->remote_host, $this->remote_dir, $this->previous_remote_target_dir);
            $this->changeSymlink($this->remote_host, $this->remote_dir, $this->previous_remote_target_dir);

            $this->postDeactivation($this->remote_host, $this->remote_dir, $this->previous_remote_target_dir);

            $this->clearRemoteCaches($this->remote_host, $this->remote_dir, $this->previous_remote_target_dir);
            $this->postRollback($this->remote_host, $this->remote_dir, $this->previous_remote_target_dir);
            $this->rollbackFiles($this->remote_host, $this->remote_dir, $this->last_remote_target_dir);
        }
    }

    /**
     * Deletes obsolete deployment directories
     */
    public function cleanup()
    {
        $this->log('cleanup', LOG_DEBUG);

        $past_deployments = array();

        if (is_array($this->remote_host)) {
            foreach ($this->remote_host as $remote_host) {
                if ($past_dirs = $this->collectPastDeployments($remote_host, $this->remote_dir)) {
                    $past_deployments[] = array(
                        'remote_host' => $remote_host,
                        'remote_dir' => $this->remote_dir,
                        'dirs' => $past_dirs
                    );
                }
            }
        } else {
            if ($past_dirs = $this->collectPastDeployments($this->remote_host, $this->remote_dir)) {
                $past_deployments[] = array(
                    'remote_host' => $this->remote_host,
                    'remote_dir' => $this->remote_dir,
                    'dirs' => $past_dirs
                );
            }
        }

        if (!empty($past_deployments)) {
            if (static::inputPrompt('Delete old directories? (yes/no): ', 'no') == 'yes') {
                $this->deletePastDeployments($past_deployments);
            }
        } else {
            $this->log('No cleanup needed');
        }
    }

    /**
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function clearRemoteCaches($remote_host, $remote_dir, $target_dir)
    {
        $this->log("clearRemoteCaches($remote_host, $remote_dir, $target_dir", LOG_DEBUG);
    }

    /**
     * Shows the file and directory changes sincs the latest deploy (rsync dry-run to the latest directory on the remote server)
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function checkFiles($remote_host, $remote_dir, $target_dir)
    {
        $this->log('checkFiles', LOG_DEBUG);

        if (!$target_dir) {
            $this->log('No deployment history found');
        }

        $this->log('Changed directories and files:', LOG_INFO, true);

        $this->rsyncExec(
            $this->rsync_path .' --rsh="ssh -p '. $this->remote_port .'" -azcO --force --dry-run --delete --progress '.
                $this->prepareExcludes() .' ./ '. $this->remote_user .'@'. $remote_host .':'. $remote_dir .'/'. $this->last_remote_target_dir,
            'Rsync check is mislukt'
        );
    }

    /**
     * Uploads files to the new directory on the remote server
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function updateFiles($remote_host, $remote_dir, $target_dir)
    {
        $this->log('updateFiles', LOG_DEBUG);

        $this->rsyncExec(
            $this->rsync_path .' --rsh="ssh -p '. $this->remote_port .'" -azcO --force --delete --progress '.
                $this->prepareExcludes() .' '. $this->prepareLinkDest($remote_dir) .' ./ '. $this->remote_user .'@'. $remote_host .':'. $remote_dir .'/'. $target_dir
        );

        $this->fixDatadirSymlinks($remote_host, $remote_dir, $target_dir);

        $this->renameTargetFiles($remote_host, $remote_dir);
    }

    /**
     * Executes the datadir patcher to create symlinks to the data dirs.
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function fixDatadirSymlinks($remote_host, $remote_dir, $target_dir)
    {
        $this->log('fixDatadirSymlinks', LOG_DEBUG);

        if (empty($this->data_dirs)) {
            return;
        }

        $this->log('Creating data dir symlinks:', LOG_DEBUG);

        $cmd = "cd $remote_dir/{$target_dir}; php {$this->datadir_patcher} --datadir-prefix={$this->data_dir_prefix} --previous-dir={$this->last_remote_target_dir} ". implode(' ', $this->data_dirs);

        $output = array();
        $return = null;
        $this->sshExec($remote_host, $cmd, $output, $return);

        $this->log($output);
    }

    /**
     * Gearman workers herstarten
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function restartGearmanWorkers($remote_host, $remote_dir, $target_dir)
    {
        $this->log("restartGearmanWorkers($remote_host, $remote_dir, $target_dir)", LOG_DEBUG);

        if (!isset($this->gearman['workers']) || empty($this->gearman['workers'])) {
            return;
        }

        $cmd = "cd $remote_dir/{$target_dir}; ";

        foreach ($this->gearman['servers'] as $server) {
            foreach ($this->gearman['workers'] as $worker) {
                $worker = sprintf($worker, $this->target);

                $cmd .= "php {$this->gearman_restarter} --ip={$server['ip']} --port={$server['port']} --function=$worker; ";
            }
        }

        $output = array();
        $return = null;

        $this->sshExec($remote_host, $cmd, $output, $return);
    }

    /**
     * Verwijdert de laatst geuploadde directory
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function rollbackFiles($remote_host, $remote_dir, $target_dir)
    {
        $this->log('rollbackFiles', LOG_DEBUG);

        $output = array();
        $return = null;
        $this->sshExec($remote_host, 'cd '. $remote_dir .'; rm -rf '. $target_dir, $output, $return);
    }

    /**
     * Update de production-symlink naar de nieuwe (of oude, bij rollback) upload directory
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function changeSymlink($remote_host, $remote_dir, $target_dir)
    {
        $this->log('changeSymlink', LOG_DEBUG);

        $output = array();
        $return = null;
        $this->sshExec($remote_host, "cd $remote_dir; rm production; ln -s {$target_dir} production", $output, $return);
    }

    /**
     * @param string $remote_host
     * @param string $remote_dir
     */
    protected function renameTargetFiles($remote_host, $remote_dir)
    {
        if (!$files_to_move = $this->listFilesToRename($remote_host, $remote_dir)) {
            return;
        }

        // configfiles verplaatsen
        $target_files_to_move = '';

        foreach ($files_to_move as $newpath => $currentpath) {
            $target_files_to_move .= "mv $currentpath $newpath; ";
        }

        $output = array();
        $return = null;
        $this->sshExec($remote_host, "cd {$remote_dir}/{$this->remote_target_dir}; $target_files_to_move", $output, $return);
    }

    /**
     * Maakt een lijst van de files die specifiek zijn voor een clusterrol of doel en op de doelserver hernoemd moeten worden
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @throws DeployException
     * @return array
     */
    protected function listFilesToRename($remote_host, $remote_dir)
    {
        if (!isset($this->files_to_rename["$remote_host-$remote_dir"])) {
            $target_files_to_move = array();

            // doelspecifieke files hernoemen
            if (!empty($this->target_specific_files)) {
                foreach ($this->target_specific_files as $filepath) {
                    $ext = pathinfo($filepath, PATHINFO_EXTENSION);

                    if (isset($target_files_to_move[$filepath])) {
                        $target_filepath = str_replace(".$ext", ".{$this->target}.$ext", $target_files_to_move[$filepath]);
                    } else {
                        $target_filepath = str_replace(".$ext", ".{$this->target}.$ext", $filepath);
                    }

                    $target_files_to_move[$filepath] = $target_filepath;
                }
            }

            // controleren of alle files bestaan
            if (!empty($target_files_to_move)) {
                foreach ($target_files_to_move as $current_filepath) {
                    if (!file_exists($current_filepath)) {
                        throw new DeployException("$current_filepath does not exist");
                    }
                }
            }

            $this->files_to_rename["$remote_host-$remote_dir"] = $target_files_to_move;
        }

        return $this->files_to_rename["$remote_host-$remote_dir"];
    }

    /**
     * Output wrapper
     *
     * @param string $message
     * @param integer $level		  LOG_INFO (6)  = normal (always show),
     *								LOG_DEBUG (7) = debugging (hidden by default)
     * @param bool $extra_newline	 Automatisch een newline aan het eind toevoegen
     */
    protected function log($message, $level = LOG_INFO, $extra_newline = false)
    {
        if (is_array($message)) {
            if (count($message) == 0) {
                return;
            }

            $message = implode(PHP_EOL, $message);
        }

        if ($level == LOG_INFO || ($this->debug && $level == LOG_DEBUG)) {
            echo $message . PHP_EOL;

            if ($extra_newline) {
                echo PHP_EOL;
            }
        }

        if ($this->logfile) {
            error_log($message . PHP_EOL, 3, $this->logfile);
        }
    }

    /**
     * Zet het array van rsync excludes om in een lijst rsync parameters
     *
     * @throws DeployException
     * @return string
     */
    protected function prepareExcludes()
    {
        $this->log('prepareExcludes', LOG_DEBUG);

        chdir($this->basedir);

        $exclude_param = '';

        if (count($this->rsync_excludes) > 0) {
            foreach ($this->rsync_excludes as $exclude) {
                if (!file_exists($exclude)) {
                    throw new DeployException('Rsync exclude file not found: '. $exclude);
                }

                $exclude_param .= '--exclude-from='. escapeshellarg($exclude) .' ';
            }
        }

        if (!empty($this->data_dirs)) {
            foreach ($this->data_dirs as $data_dir) {
                $exclude_param .= '--exclude '. escapeshellarg("/$data_dir") .' ';
            }
        }

        return $exclude_param;
    }

    /**
     * Bereidt de --copy-dest parameter voor rsync voor als dat van toepassing is
     *
     * @param string $remote_dir
     * @return string
     */
    protected function prepareLinkDest($remote_dir)
    {
        $this->log('prepareLinkDest', LOG_DEBUG);

        if ($remote_dir === null) {
            $remote_dir = $this->remote_dir;
        }

        $linkdest = '';

        if ($this->last_remote_target_dir) {
            $linkdest = "--copy-dest=$remote_dir/{$this->last_remote_target_dir}";
        }

        return $linkdest;
    }

    /**
     * Initializes the remote project and data directories.
     *
     * @param string $remote_host
     * @param string $remote_dir
     */
    protected function prepareRemoteDirectory($remote_host, $remote_dir)
    {
        $this->log('Initialize remote directory: '. $remote_host .':'. $remote_dir, LOG_INFO, true);

        $output = array();
        $return = null;
        $this->sshExec($remote_host, "mkdir -p $remote_dir", $output, $return, '', '', LOG_DEBUG);

        if (empty($this->data_dirs)) {
            return;
        }

        $data_dirs = count($this->data_dirs) > 1 ? '{'. implode(',', $this->data_dirs) .'}' : implode(',', $this->data_dirs);

        $cmd = "mkdir -v -m 0775 -p $remote_dir/{$this->data_dir_prefix}/$data_dirs";

        $output = array();
        $return = null;
        $this->sshExec($remote_host, $cmd, $output, $return, '', '', LOG_DEBUG);
    }

    /**
     * Returns the timestamps of the second latest and latest deployments
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @throws DeployException
     * @return array [previous_timestamp, last_timestamp]
     */
    protected function findPastDeploymentTimestamps($remote_host, $remote_dir)
    {
        $this->log('findPastDeploymentTimestamps', LOG_DEBUG);

        $this->prepareRemoteDirectory($remote_host, $remote_dir);

        if ($remote_dir === null) {
            $remote_dir = $this->remote_dir;
        }

        $dirs = array();
        $return = null;
        $this->sshExec($remote_host, "ls -1 $remote_dir", $dirs, $return, '', '', LOG_DEBUG);

        if ($return !== 0) {
            throw new DeployException('ssh initialize failed');
        }

        if (!count($dirs)) {
            return null;
        }

        $past_deployments = array();
        $deployment_timestamps = array();

        foreach ($dirs as $dirname) {
            if (
                preg_match('/'. preg_quote($this->project_name) .'_\d{4}-\d{2}-\d{2}_\d{6}/', $dirname) &&
                ($time = strtotime(str_replace(array($this->project_name .'_', '_'), array('', ' '), $dirname)))
            ) {
                $past_deployments[] = $dirname;
                $deployment_timestamps[] = $time;
            }
        }

        $count = count($deployment_timestamps);

        if ($count == 0) {
            return null;
        }

        $this->log('Past deployments:', LOG_INFO, true);
        $this->log($past_deployments, LOG_INFO, true);

        sort($deployment_timestamps);

        if ($count >= 2) {
            return array_slice($deployment_timestamps, -2);
        }

        return array(null, array_pop($deployment_timestamps));
    }

    /**
     * Returns all obsolete deployments that can be deleted.
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @throws DeployException
     * @return array
     */
    protected function collectPastDeployments($remote_host, $remote_dir)
    {
        $this->log('collectPastDeployments', LOG_DEBUG);

        $dirs = array();
        $return = null;
        $this->sshExec($remote_host, "ls -1 $remote_dir", $dirs, $return);

        if ($return !== 0) {
            throw new DeployException('ssh initialize failed');
        }

        if (!count($dirs)) {
            return null;
        }

        $deployment_dirs = array();

        foreach ($dirs as $dirname) {
            if (preg_match('/'. preg_quote($this->project_name) .'_\d{4}-\d{2}-\d{2}_\d{6}/', $dirname)) {
                $deployment_dirs[] = $dirname;
            }
        }

        // the two latest deployments always stay
        if (count($deployment_dirs) <= 2) {
            return null;
        }

        $dirs_to_delete = array();

        sort($deployment_dirs);

        $deployment_dirs = array_slice($deployment_dirs, 0, -2);

        foreach ($deployment_dirs as $key => $dirname) {
            $time = strtotime(str_replace(array($this->project_name .'_', '_'), array('', ' '), $dirname));

            if ($time < strtotime('-1 month')) {
                // deployments older than a month can go
                $this->log("$dirname is older than a month");

                $dirs_to_delete[] = $dirname;
            } elseif ($time < strtotime('-1 week')) {
                // of deployments older than a week only the last one of the day stays
                if (isset($deployment_dirs[$key+1])) {
                    $time_next = strtotime(str_replace(array($this->project_name .'_', '_'), array('', ' '), $deployment_dirs[$key+1]));

                    // if the next deployment was on the same day this one can go
                    if (date('j', $time_next) == date('j', $time)) {
                        $this->log("$dirname was replaced the same day");

                        $dirs_to_delete[] = $dirname;
                    } else {
                        $this->log("$dirname stays");
                    }
                }
            } else {
                $this->log("$dirname stays");
            }
        }

        return $dirs_to_delete;
    }

    /**
     * Deletes obsolete deployments as collected by collectPastDeployments
     *
     * @param array $past_deployments
     */
    protected function deletePastDeployments($past_deployments)
    {
        foreach ($past_deployments as $past_deployment) {
            $this->rollbackFiles($past_deployment['remote_host'], $past_deployment['remote_dir'], implode(' ', $past_deployment['dirs']));
        }
    }

    /**
     * Wrapper for SSH command's
     *
     * @param string $remote_host
     * @param string $command
     * @param array $output
     * @param int $return
     * @param string $hide_pattern		Regexp to clean up output (eg. passwords)
     * @param string $hide_replacement
     * @param int $ouput_loglevel
     */
    protected function sshExec($remote_host, $command, &$output, &$return, $hide_pattern = '', $hide_replacement = '', $ouput_loglevel = LOG_INFO)
    {
        $cmd = $this->ssh_path .' -p '. $this->remote_port .' '. $this->remote_user .'@'. $remote_host .' "'. str_replace('"', '\"', $command) .'"';

        if ($hide_pattern != '') {
            $show_cmd = preg_replace($hide_pattern, $hide_replacement, $cmd);
        } else {
            $show_cmd = $cmd;
        }

        $this->log('sshExec: '. $show_cmd, $ouput_loglevel);

        exec($cmd, $output, $return);
    }

    /**
     * Wrapper for rsync command's
     *
     * @param string $command
     * @param string $error_msg
     * @throws DeployException
     */
    protected function rsyncExec($command, $error_msg = 'Rsync has failed')
    {
        $this->log('execRSync: '. $command, LOG_DEBUG);

        chdir($this->basedir);

        passthru($command, $return);

        $this->log('');

        if ($return !== 0) {
            throw new DeployException($error_msg);
        }
    }

    /**
     * Asks the user for input
     *
     * @param string $message
     * @param string $default
     * @param boolean $isPassword
     * @return string
     */
    static protected function inputPrompt($message, $default = '', $isPassword = false)
    {
        fwrite(STDOUT, $message);

        if (!$isPassword) {
            $input = trim(fgets(STDIN));
        } else {
            $input = self::getPassword(false);
            echo PHP_EOL;
        }

        if ($input == '') {
            $input = $default;
        }

        return $input;
    }

    /**
     * Stub method for code to be run *before* deployment
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function preDeploy($remote_host, $remote_dir, $target_dir)
    {
        $this->log("preDeploy($remote_host, $remote_dir, $target_dir)", LOG_DEBUG);
    }

    /**
     * Stub method for code to run right before the symlinks are updated to the new deploy.
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function preActivation($remote_host, $remote_dir, $target_dir)
    {
        $this->log("preActivation($remote_host, $remote_dir, $target_dir)", LOG_DEBUG);
    }

    /**
     * Stub method for code to be run *after* deployment and *before* the cache clears
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function postDeploy($remote_host, $remote_dir, $target_dir)
    {
        $this->log("postDeploy($remote_host, $remote_dir, $target_dir)", LOG_DEBUG);

        $this->restartGearmanWorkers($remote_host, $remote_dir, $target_dir);
    }

    /**
     * Stub methode voor extra uitbreidingen die *voor* rollback worden uitgevoerd
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function preRollback($remote_host, $remote_dir, $target_dir)
    {
        $this->log("preRollback($remote_host, $remote_dir, $target_dir)", LOG_DEBUG);
    }

    /**
     * Stub method for code to run right after the symlinks have been reverted to the previous deploy.
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function postDeactivation($remote_host, $remote_dir, $target_dir)
    {
        $this->log("preRollback($remote_host, $remote_dir, $target_dir)", LOG_DEBUG);
    }

    /**
     * Stub methode voor extra uitbreidingen die *na* rollback worden uitgevoerd
     *
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function postRollback($remote_host, $remote_dir, $target_dir)
    {
        $this->log("postRollback($remote_host, $remote_dir, $target_dir)", LOG_DEBUG);

        $this->restartGearmanWorkers($remote_host, $remote_dir, $target_dir);
    }

    /**
     * Get a password from the shell.
     *
     * This function works on *nix systems only and requires shell_exec and stty.
     *
     * @author http://www.dasprids.de/blog/2008/08/22/getting-a-password-hidden-from-stdin-with-php-cli
     * @param  boolean $stars Wether or not to output stars for given characters
     * @return string
     */
    static protected function getPassword($stars = false)
    {
        // Get current style
        $oldStyle = shell_exec('stty -g');

        if ($stars === false) {
            shell_exec('stty -echo');
            $password = rtrim(fgets(STDIN), "\n");
        } else {
            shell_exec('stty -icanon -echo min 1 time 0');

            $password = '';
            while (true) {
                $char = fgetc(STDIN);

                if ($char === "\n") {
                    break;
                } else if (ord($char) === 127) {
                    if (strlen($password) > 0) {
                        fwrite(STDOUT, "\x08 \x08");
                        $password = substr($password, 0, -1);
                    }
                } else {
                    fwrite(STDOUT, "*");
                    $password .= $char;
                }
            }
        }

        // Reset old style
        shell_exec('stty ' . $oldStyle);

        // Return the password
        return $password;
    }
}
