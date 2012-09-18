<?php

/**
 * The deployer
 */
class BaseDeploy
{
	/**
	 * Formattering van de deployment directories
	 *
	 * @var string
	 */
	protected $remote_dir_format = '%project_name%_%timestamp%';

	/**
	 * Datum formattering in de naam van de deployment directories
	 * (format parameter van date())
	 *
	 * @var string
	 */
	protected $remote_dir_timestamp_format = 'Y-m-d_His';

	/**
	 * De codenaam van de applicatie
	 *
	 * @var string
	 */
	protected $project_name = null;

	/**
	 * De root directory van het project
	 *
	 * @var string
	 */
	protected $basedir = null;

	/**
	 * De hostname van de remote server
	 *
	 * @var string
	 */
	protected $remote_host = null;

	/**
	 * De gebruikersnaam van het account op de remote server
	 *
	 * @var string
	 */
	protected $remote_user = null;

	/**
	 * De directory op de remote server waar dit project staat
	 *
	 * @var string
	 */
	protected $remote_dir = null;

	/**
	 * Alle bestanden die als rsync exclude moeten worden gebruikt
	 *
	 * @var array
	 */
	protected $rsync_excludes = array();

	/**
	 * De timestamp voor deze deployment waarmee gewerkt gaat worden
	 *
	 * @var timestamp
	 */
	protected $timestamp = null;

	/**
	 * De directory waar de nieuwe deploy terecht gaat komen
	 *
	 * @var string
	 */
	protected $remote_target_dir = null;

	/**
	 * De timestamp van de voorlaatste deployment
	 *
	 * @var timestamp
	 */
	protected $previous_timestamp = null;

	/**
	 * De timestamp van de laatste deployment
	 *
	 * @var timestamp
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
	 * Alle directories die moeten worden doorzocht naar SQL update files
	 *
	 * @var array
	 */
	protected $database_dirs = array();

	/**
	 * De hostname van de database server
	 *
	 * @var string
	 */
	protected $database_host = null;

	/**
	 * De naam van de database waar de SQL updates naartoe gaan
	 *
	 * @var string
	 */
	protected $database_name = null;

	/**
	 * De gebruikersnaam van de database
	 *
	 * @var string
	 */
	protected $database_user = null;

	/**
	 * Het wachtwoord dat bij de gebruikersnaam hoort
	 *
	 * @var string
	 */
	protected $database_pass = null;

	/**
	 * Of de database-gegevens gecontroleerd zijn
	 *
	 * @var boolean
	 */
	protected $database_checked = false;

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
	 * Het pad van de database patcher, relatief vanaf de project root
	 *
	 * @var string
	 */
	protected $database_patcher = null;

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
	 * 		'config/databases.yml'
	 *
	 * bij publicatie naar stage gebeurd dit:
	 * 		'config/databases.stage.yml' => 'config/databases.yml'
	 *
	 * bij publicatie naar prod gebeurd dit:
	 * 		'config/databases.prod.yml' => 'config/databases.yml'
	 *
	 * @var array
	 */
	protected $target_specific_files = array();

	/**
	 * settings van gearman inclusief worker functies die herstart moeten worden
	 * na deployment
	 *
	 * voorbeeld:
	 * 		array(
	 *			'servers' => array(
	 *				array('ip' => ipadres, 'port' => gearmanport)
	 * 			),
	 * 			'workers' => array(
	 *				'functienaam1',
	 *				'functienaam2',
	 * 			)
	 * 		)
	 *
	 * @var array
	 */
	protected $gearman = array();

	/**
	 * Cache voor listFilesToRename()
	 *
	 * @var array
	 */
	protected $files_to_rename = array();

	/**
	 * The project path where the deploy_timestamp.php template is located
	 *
	 * @var string
	 */
	protected $apc_deploy_timestamp_template = null;

	/**
	 * The absolute physical path where the deploy_timestamp.php should be placed (on the remote server)
	 *
	 * @var string
	 */
	protected $apc_deploy_timestamp_path = null;

	/**
	 * The local url (on the remote server) where setrev.php can be reached
	 *
	 * @var string
	 */
	protected $apc_deploy_setrev_url = null;

	/**
	 * Programma paden
	 */
	protected $rsync_path = 'rsync';
	protected $ssh_path = 'ssh';

	/**
	 * Bouwt een nieuwe Deploy class op met de gegeven opties
	 *
	 * @param array $options
	 */
	public function __construct(array $options)
	{
		$this->project_name				= $options['project_name'];
		$this->basedir					= $options['basedir'];
		$this->remote_host				= $options['remote_host'];
		$this->remote_user				= $options['remote_user'];
		$this->database_dirs			= isset($options['database_dirs']) ? (array) $options['database_dirs'] : array();
		$this->target					= $options['target'];
		$this->remote_dir				= $options['remote_dir'] .'/'. $this->target;
		$this->database_patcher 		= isset($options['database_patcher']) ? $options['database_patcher'] : null;

		// als database host niet wordt meegegeven automatisch de eerste remote host (clustermaster) pakken.
		$this->database_host	= isset($options['database_host']) ? $options['database_host'] : (is_array($this->remote_host) ? $this->remote_host[0] : $this->remote_host);

		if (isset($options['database_name']))
			$this->database_name = $options['database_name'];

		if (isset($options['database_user']))
			$this->database_user = $options['database_user'];

		if (isset($options['database_pass']))
			$this->database_pass = $options['database_pass'];

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

		if (isset($options['apc_deploy_version_template']) && isset($options['apc_deploy_version_path']) && isset($options['apc_deploy_setrev_url'])) {
			$this->apc_deploy_version_template = $options['apc_deploy_version_template'];
			$this->apc_deploy_version_path = $options['apc_deploy_version_path'];
			$this->apc_deploy_setrev_url = $options['apc_deploy_setrev_url'];
        }

		$this->rsync_path		= isset($options['rsync_path']) ? $options['rsync_path'] : trim(`which rsync`);
		$this->ssh_path			= isset($options['ssh_path']) ? $options['ssh_path'] : trim(`which ssh`);

		if (!$this->auto_init)
			return;

		echo PHP_EOL . 'find timestamps: '. PHP_EOL;

		$this->initialize();
	}

	/**
	 * Bepaalt de timestamp van een nieuwe deployment en de timestamps van de laatste en voorlaatste deployment
	 */
	protected function initialize()
	{
		$this->log('initialisatie', 2);

		// als er meerdere remote hosts zijn de eerste (cluster master) alvast initialiseren zodat het zoeken naar timestamps goed gaat
		$remote_host = is_array($this->remote_host) ? $this->remote_host[0] : $this->remote_host;

		$this->timestamp = time();
		$this->remote_target_dir = strtr($this->remote_dir_format, array('%project_name%' => $this->project_name, '%timestamp%' => date($this->remote_dir_timestamp_format, $this->timestamp)));

		list($this->previous_timestamp, $this->last_timestamp) = $this->findPastDeploymentTimestamps($remote_host);

		if ($this->previous_timestamp)
		{
			$this->previous_remote_target_dir = strtr($this->remote_dir_format, array('%project_name%' => $this->project_name, '%timestamp%' => date($this->remote_dir_timestamp_format, $this->previous_timestamp)));
		}

		if ($this->last_timestamp)
		{
			$this->last_remote_target_dir = strtr($this->remote_dir_format, array('%project_name%' => $this->project_name, '%timestamp%' => date($this->remote_dir_timestamp_format, $this->last_timestamp)));
		}
	}

	/**
	 * Draait een dry-run naar de remote server om de gewijzigde bestanden te tonen
	 *
	 * @param string $action 		update of rollback
     * @return bool
     */
	protected function check($action)
	{
		$this->log('check', 2);

		if (is_array($this->remote_host))
		{
			foreach ($this->remote_host as $key => $remote_host)
			{
				if ($key == 0) continue;

				$this->prepareRemoteDirectory($remote_host, $this->generateClusterDirname($key, $this->remote_dir));
			}
		}

		if ($action == 'update')
			$this->checkFiles(is_array($this->remote_host) ? $this->remote_host[0] : $this->remote_host);

		if (!empty($this->database_dirs))
			$this->checkDatabase($this->database_host, $action);

        if ($this->apc_deploy_version_template) {
            if (!file_exists($this->apc_deploy_version_template)) {
                throw new DeployException("{$this->apc_deploy_version_template} does not exist.");
            }
        }

		if ($action == 'update')
		{
			if (is_array($this->remote_host))
			{
				// eerst preDeploy draaien per host, dan alle files synchen
				foreach ($this->remote_host as $key => $remote_host)
				{
					$remote_dir = $this->generateClusterDirname($key, $this->remote_dir);

					if ($files = $this->listFilesToRename($remote_host, $remote_dir))
					{
						$this->log("Files verplaatsen op $remote_host:");

						foreach ($files as $filepath => $newpath)
							$this->log("  $newpath => $filepath");
					}
				}
			}
			else
			{
				if ($files = $this->listFilesToRename($this->remote_host, $this->remote_dir))
				{
					$this->log('Files verplaatsen:');

					foreach ($files as $filepath => $newpath)
						$this->log("  $newpath => $filepath");
				}
			}
		}

		// als alles goed is gegaan kan er doorgegaan worden met de deployment
		if ($action == 'update')
			return $this->inputPrompt('Proceed with deployment? (yes/no): ', 'no') == 'yes';
		elseif ($action == 'rollback')
			return $this->inputPrompt('Proceed with rollback? (yes/no): ', 'no') == 'yes';
	}

	/**
	 * Zet het project online en voert database-aanpassingen uit
	 * Kan alleen worden uitgevoerd nadat check() heeft gedraaid
	 */
	public function deploy()
	{
		$this->log('deploy', 2);

		if (!$this->check('update'))
			return;

		if (is_array($this->remote_host))
		{
			// eerst preDeploy draaien per host, dan alle files synchen
			foreach ($this->remote_host as $key => $remote_host)
			{
				$remote_dir = $this->generateClusterDirname($key, $this->remote_dir);

				$this->preDeploy($remote_host, $remote_dir, $this->remote_target_dir);
				$this->updateFiles($remote_host, $remote_dir, $this->remote_target_dir);
			}

			// na de uploads de database prepareren
			if (!empty($this->database_dirs))
				$this->updateDatabase($this->database_host, $this->remote_dir, $this->remote_target_dir);

			// als de files en database klaarstaan kan de nieuwe versie geactiveerd worden
			// door de symlinks te updaten en postDeploy te draaien
			foreach ($this->remote_host as $key => $remote_host)
			{
				$remote_dir = $this->generateClusterDirname($key, $this->remote_dir);

				$this->changeSymlink($remote_host, $remote_dir, $this->remote_target_dir);
				$this->postDeploy($remote_host, $remote_dir, $this->remote_target_dir);
				$this->clearRemoteCaches($remote_host, $remote_dir, $this->remote_target_dir);
			}
		}
		else
		{
			$this->preDeploy($this->remote_host, $this->remote_dir, $this->remote_target_dir);
			$this->updateFiles($this->remote_host, $this->remote_dir, $this->remote_target_dir);

			if (!empty($this->database_dirs))
				$this->updateDatabase($this->database_host, $this->remote_dir, $this->remote_target_dir);

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
		$this->log('rollback', 2);

		if ($this->previous_remote_target_dir)
		{
			if (!$this->check('rollback'))
				return;

			if (is_array($this->remote_host))
			{
				// eerst op alle hosts de symlink terugdraaien
				foreach ($this->remote_host as $key => $remote_host)
				{
					$remote_dir = $this->generateClusterDirname($key, $this->remote_dir);

					$this->preRollback($remote_host, $remote_dir, $this->previous_remote_target_dir);
					$this->changeSymlink($remote_host, $remote_dir, $this->previous_remote_target_dir);
				}

				// nadat de symlinks zijn teruggedraaid de database terugdraaien
				if (!empty($this->database_dirs))
					$this->rollbackDatabase($this->database_host, $this->generateClusterDirname($this->database_host, $this->remote_dir));

				// de caches resetten
				foreach ($this->remote_host as $key => $remote_host)
				{
					$remote_dir = $this->generateClusterDirname($key, $this->remote_dir);

					$this->clearRemoteCaches($remote_host, $remote_dir, $this->previous_remote_target_dir);
					$this->postRollback($remote_host, $remote_dir, $this->previous_remote_target_dir);
				}

				// als laatste de nieuwe directory terugdraaien
				foreach ($this->remote_host as $key => $remote_host)
				{
					$remote_dir = $this->generateClusterDirname($key, $this->remote_dir);

					$this->rollbackFiles($remote_host, $remote_dir, $this->last_remote_target_dir);
				}
			}
			else
			{
				$this->preRollback($this->remote_host, $this->remote_dir, $this->previous_remote_target_dir);
				$this->changeSymlink($this->remote_host, $this->remote_dir, $this->previous_remote_target_dir);

				if (!empty($this->database_dirs))
					$this->rollbackDatabase($this->database_host, $this->remote_dir);

				$this->clearRemoteCaches($this->remote_host, $this->remote_dir, $this->previous_remote_target_dir);
				$this->postRollback($this->remote_host, $this->remote_dir, $this->previous_remote_target_dir);
				$this->rollbackFiles($this->remote_host, $this->remote_dir, $this->last_remote_target_dir);
			}
		}
		else
		{
			$this->log('Rollback impossible, no previous deployment found !');
		}
	}

	/**
	 * Verwijdert oude deployment directories
	 */
	public function cleanup()
	{
		$this->log('cleanup', 2);

		$past_deployments = array();

		if (is_array($this->remote_host))
		{
			foreach ($this->remote_host as $key => $remote_host)
			{
				$remote_dir = $this->generateClusterDirname($key, $this->remote_dir);

				if ($past_dirs = $this->collectPastDeployments($remote_host, $remote_dir))
				{
					$past_deployments[] = array(
						'remote_host' => $remote_host,
						'remote_dir' => $remote_dir,
						'dirs' => $past_dirs
					);
				}
			}
		}
		else
		{
			if ($past_dirs = $this->collectPastDeployments($this->remote_host, $this->remote_dir))
			{
				$past_deployments[] = array(
					'remote_host' => $this->remote_host,
					'remote_dir' => $this->remote_dir,
					'dirs' => $past_dirs
				);
			}
		}

		if (!empty($past_deployments)) {
			if ($this->inputPrompt('Delete old directories? (yes/no): ', 'no') == 'yes')
				$this->deletePastDeployments($past_deployments);
		}
		else {
			$this->log('No cleanup needed');
		}
	}

	protected function generateClusterDirname($key, $remote_dir)
	{
		return $key == 0 ? $remote_dir : str_replace('clustermaster', 'clusternode', $remote_dir);
	}

    /**
     * @param string $remote_host
     * @param string $remote_dir
     * @param string $target_dir
     */
    protected function clearRemoteCaches($remote_host, $remote_dir, $target_dir)
	{
        if ($this->apc_deploy_version_template && $this->apc_deploy_version_path && $this->apc_deploy_setrev_url) {
            $output = array();
            $return = null;
            $this->sshExec($remote_host,
                    "cd $remote_dir/$target_dir; ".
                    "cp {$this->apc_deploy_version_template} {$this->apc_deploy_version_path}.tmp; ".
                    "sed -i 's/#deployment_timestamp#/{$this->timestamp}/' {$this->apc_deploy_version_path}.tmp; ".
                    "mv {$this->apc_deploy_version_path}.tmp {$this->apc_deploy_version_path}; ".
                    "curl -s -S {$this->apc_deploy_setrev_url}?rev={$this->timestamp}",
                $output, $return);

            $this->log($output);

            if ($return != 0)
                $this->log("$remote_host: Clear cache failed");
        }
	}

	/**
	 * Toont de files die veranderd zijn sinds de laatste upload (rsync dry-run output tegen de laatste directory online)
	 */
	protected function checkFiles($remote_host = null, $remote_dir = null)
	{
		$this->log('checkFiles', 2);

		if ($remote_host === null)
			$remote_host = $this->remote_host;

		if ($remote_dir === null)
			$remote_dir = $this->remote_dir;

		if ($this->last_remote_target_dir)
		{
			$this->rsyncExec($this->rsync_path .' -azcO --force --dry-run --delete --progress '. $this->prepareExcludes() .' ./ '. $this->remote_user .'@'. $remote_host .':'. $remote_dir .'/'. $this->last_remote_target_dir, 'Rsync check is mislukt');
		}
		else
		{
			$this->log('geen deployment geschiedenis gevonden');
		}
	}

	/**
	 * Uploadt files naar een nieuwe directory op de live server
	 */
	protected function updateFiles($remote_host, $remote_dir, $target_dir)
	{
		$this->log('updateFiles', 2);

		$this->rsyncExec($this->rsync_path .' -azcO --force --delete --progress '. $this->prepareExcludes() .' '. $this->prepareLinkDest($remote_dir) .' ./ '. $this->remote_user .'@'. $remote_host .':'. $remote_dir .'/'. $target_dir);

		$this->fixDatadirSymlinks($remote_host, $remote_dir, $target_dir);

		$this->renameTargetFiles($remote_host, $remote_dir);
	}

	protected function fixDatadirSymlinks($remote_host, $remote_dir, $target_dir)
	{
		$this->log('fixDatadirSymlinks', 2);

		if (!empty($this->data_dirs))
		{
			$cmd = "cd $remote_dir/{$target_dir}; php {$this->datadir_patcher} --datadir-prefix={$this->data_dir_prefix} --previous-dir={$this->last_remote_target_dir} ". implode(' ', $this->data_dirs);

			$output = array();
			$return = null;
			$this->sshExec($remote_host, $cmd, $output, $return);

			$this->log($output);
		}
	}

	/**
	 * Gearman workers herstarten
	 */
	protected function restartGearmanWorkers($remote_host, $remote_dir, $target_dir)
	{
		if (isset($this->gearman['workers']) && !empty($this->gearman['workers'])) {

			foreach ($this->gearman['servers'] as $server) {

				foreach ($this->gearman['workers'] as $worker) {

					$worker = sprintf($worker, $this->target);

					$cmd = "cd $remote_dir/{$target_dir}; php {$this->gearman_restarter} --ip={$server['ip']} --port={$server['port']} --function=$worker";

					$this->log("restartGearmanWorkers($remote_host, $remote_dir, $target_dir): $cmd", 2);

					$output = array();
					$return = null;
					$this->sshExec($remote_host, $cmd, $output, $return);
				}
			}
		}
	}

	/**
	 * Verwijdert de laatst geuploadde directory
	 */
	protected function rollbackFiles($remote_host, $remote_dir, $target_dir)
	{
		$this->log('rollbackFiles', 2);

		$output = array();
		$return = null;
		$this->sshExec($remote_host, 'cd '. $remote_dir .'; rm -rf '. $target_dir, $output, $return);
	}

	/**
	 * Update de production-symlink naar de nieuwe (of oude, bij rollback) upload directory
	 */
	protected function changeSymlink($remote_host, $remote_dir, $target_dir)
	{
		$this->log('changeSymlink', 2);

		$output = array();
		$return = null;
		$this->sshExec($remote_host, "cd $remote_dir; rm production; ln -s {$target_dir} production", $output, $return);
	}

	/**
	 * Voert database migraties uit voor de nieuwste upload
	 *
	 * @param string $database_host
	 * @param string $action 			update of rollback
	 */
	protected function checkDatabase($database_host, $action)
	{
		$this->log('checkDatabase', 2);

		if ($action == 'update')
			$files = $this->findSQLFilesForPeriod($this->last_timestamp, $this->timestamp);
		elseif ($action == 'rollback')
			$files = $this->findSQLFilesForPeriod($this->last_timestamp, $this->previous_timestamp);

		if (!($files))
		{
			$this->log('geen database updates gevonden');
		}
		else
		{
			self::checkDatabaseFiles($this->target, $this->basedir, $files);

			if ($action == 'update')
				$msg = 'database updates die uitgevoerd zullen worden:';
			elseif ($action == 'rollback')
				$msg = 'database rollbacks die uitgevoerd zullen worden:';

			$this->log($msg);
			$this->log($files);

			$this->getDatabaseLogin($database_host);
		}
	}

	/**
	 * Voert database migraties uit voor de nieuwste upload
	 *
	 * @param string $database_host
	 * @param string $remote_dir
	 */
	protected function updateDatabase($database_host, $remote_dir, $target_dir)
	{
		$this->log('updateDatabase', 2);

		if (!($files = $this->findSQLFilesForPeriod($this->last_timestamp, $this->timestamp)))
		{
			$this->log('geen database updates gevonden');

			return;
		}
		else
		{
			self::checkDatabaseFiles($this->target, $this->basedir, $files);

			$this->log('database updates die uitgevoerd zullen worden:');
			$this->log($files);

			$this->getDatabaseLogin($database_host);

			$this->sendToDatabase($database_host, "cd $remote_dir/{$target_dir}; php {$this->database_patcher} update ". implode(' ', $files), $output, $return, $this->database_name, $this->database_user, $this->database_pass);
		}
	}

    /**
     * Reverts database migrations to the previous deployment
     *
     * @param string $database_host
     * @param string $remote_dir
     */
	protected function rollbackDatabase($database_host, $remote_dir)
	{
		$this->log('rollbackDatabase', 2);

		if (!($files = $this->findSQLFilesForPeriod($this->last_timestamp, $this->previous_timestamp)))
		{
			$this->log('geen database updates gevonden');

			return;
		}
		else
		{
			self::checkDatabaseFiles($this->target, $this->basedir, $files);

			$this->log('database rollbacks die uitgevoerd zullen worden:');
			$this->log($files);

			$this->getDatabaseLogin($database_host);

			$this->sendToDatabase($database_host, "cd $remote_dir/{$this->last_remote_target_dir}; php {$this->database_patcher} rollback ". implode(' ', $files), $output, $return, $this->database_name, $this->database_user, $this->database_pass);
		}
	}

    /**
     * @param string $remote_host
     * @param string $remote_dir
     */
    protected function renameTargetFiles($remote_host, $remote_dir)
	{
		if ($files_to_move = $this->listFilesToRename($remote_host, $remote_dir))
		{
			// configfiles verplaatsen
			$target_files_to_move = '';

			foreach ($files_to_move as $newpath => $currentpath)
				$target_files_to_move .= "mv $currentpath $newpath; ";

			$output = array();
			$return = null;
			$this->sshExec($remote_host, "cd {$remote_dir}/{$this->remote_target_dir}; $target_files_to_move", $output, $return);
		}
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
		if (!isset($this->files_to_rename["$remote_host-$remote_dir"]))
		{
			$cluster_role = (strpos($remote_dir, 'clustermaster') !== false) ? 'master' : ((strpos($remote_dir, 'clusternode') !== false) ? 'node' : '');

			$target_files_to_move = array();

			// doelspecifieke files hernoemen
			if (!empty($this->target_specific_files))
			{
				foreach ($this->target_specific_files as $filepath)
				{
					$ext = pathinfo($filepath, PATHINFO_EXTENSION);

					if (isset($target_files_to_move[$filepath]))
					{
						$target_filepath = str_replace(".$ext", ".{$this->target}.$ext", $target_files_to_move[$filepath]);
					}
					else
					{
						$target_filepath = str_replace(".$ext", ".{$this->target}.$ext", $filepath);
					}

					$target_files_to_move[$filepath] = $target_filepath;
				}
			}

			// controleren of alle files bestaan
			if (!empty($target_files_to_move))
			{
				foreach ($target_files_to_move as $current_filepath)
				{
					if (!file_exists($current_filepath))
					{
						throw new DeployException("$current_filepath does not exist");
					}
				}
			}

			$this->files_to_rename["$remote_host-$remote_dir"] = $target_files_to_move;
		}

		return $this->files_to_rename["$remote_host-$remote_dir"];
	}

	/**
	 * Controleert of alle opgegeven bestanden bestaan en de juist class en sql code bevatten
	 *
	 * @param string $update		update of rollback
	 * @param array $filenames
	 * @returns array				De absolute paden van alle files
	 */
	static public function checkDatabaseFiles($action, $path_prefix, $filenames)
	{
		$classes = array();

		foreach ($filenames as $filename)
		{
			$filepath = $path_prefix .'/'. $filename;

			if (!file_exists($filepath))
				throw new DeployException("$filepath not found");

			$classname = str_replace('.class', '', pathinfo($filename, PATHINFO_FILENAME));

			require_once $filepath;

			if (!class_exists($classname))
				throw new DeployException("Class $classname not found in $filepath");

			$sql = new $classname();

			if (!$sql instanceof SQL_update)
				throw new DeployException("Class $classname doesn't implement SQL_update");

			$up_sql = trim($sql->up());

			if ($up_sql != '' && substr($up_sql, -1) != ';')
				throw new DeployException("$classname up() code doesn't end with ';'");

			$down_sql = trim($sql->down());

			if ($down_sql != '' && substr($down_sql, -1) != ';')
				throw new DeployException("$classname down() code doesn't end with ';'");

			$classes[] = $sql;
		}

		return $classes;
	}

	protected function getDatabaseLogin($database_host)
	{
		if ($this->database_checked)
			return;

		if ($this->database_name !== null) {
			$database_name = self::inputPrompt('Database ('. $this->database_name .') [skip]: ', 'skip');
		} else {
			$database_name = self::inputPrompt('Database [skip]: ', 'skip');
		}

		if ($database_name == 'skip') {
			$username = '';
			$password = '';

			$this->log('database skipped');
		}
		else {
			$username = $this->database_user !== null ? $this->database_user : self::inputPrompt('Database username [root]: ', 'root');
			$password = $this->database_pass !== null ? $this->database_pass : self::inputPrompt('Database password: ', '', true);

			// controleren of deze gebruiker een tabel mag aanmaken (rudimentaire toegangstest)
			$this->sendToDatabase($database_host, "echo '". addslashes("CREATE TABLE temp_{$this->timestamp} (field1 INT NULL); DROP TABLE temp_{$this->timestamp};") ."'", $output, $return, $database_name, $username, $password);

			if ($return != 0)
				return $this->getDatabaseLogin($database_host);

			$this->log('database check passed');
		}

		$this->database_checked = true;
		$this->database_name = $database_name;
		$this->database_user = $username;
		$this->database_pass = $password;
	}

    /**
     * Stuurt een query naar de database.
     *
     * @param string $database_host
     * @param string $command
     * @param array $output
     * @param integer $return
     * @param string $database_name
     * @param string $username
     * @param string $password
     */
    protected function sendToDatabase($database_host, $command, &$output, &$return, $database_name, $username, $password)
	{
		if ($this->database_checked && $this->database_name == 'skip')
			return;

		$output = array();
		$return = null;
		$this->sshExec($database_host, "$command | mysql -u$username -p$password $database_name", $output, $return, '/ mysql -u([^ ]+) -p[^ ]+ /', ' mysql -u$1 -p***** ');
	}

    /**
     * Maakt een lijstje van alle SQL update files die binnen het timeframe vallen, in de volgorde die de start- en endtime impliceren.
     * Als de starttime *voor* de endtime ligt is het een gewone update cyclus en worden de files chronologisch gerangschikt.
     * Als de starttime *na* de endtime ligt is het een rollback en worden de files van nieuw naar oud gerangschikt.
     *
     * @param integer $starttime (timestamp)
     * @param integer $endtime (timestamp)
     * @throws DeployException
     * @return array
     */
	public function findSQLFilesForPeriod($starttime, $endtime)
	{
		$this->log('findSQLFilesForPeriod('. date('Y-m-d H:i:s', $starttime) .','. date('Y-m-d H:i:s', $endtime) .')', 2);

		$reverse = $starttime > $endtime;

		if ($reverse)
		{
			$starttime2 = $starttime;
			$starttime = $endtime;
			$endtime = $starttime2;
			unset($starttime2);
		}

		$update_files = array();

		foreach ($this->database_dirs as $database_dir)
		{
			$dir = new DirectoryIterator($database_dir);

			foreach ($dir as $entry)
			{
				if (!$entry->isDot() && $entry->isFile())
				{
					if (preg_match('/sql_(\d{8}_\d{6})\.class.php/', $entry->getFilename(), $matches))
					{
						if (!($timestamp = strtotime(preg_replace('/(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2})/', '$1-$2-$3 $4:$5:$6', $matches[1]))))
							throw new DeployException('Kan '. $matches[1] .' niet converteren naar timestamp');

						if ($timestamp > $starttime && $timestamp < $endtime)
							$update_files[$timestamp] = $entry->getPathname();
					}
				}
			}
		}

		if (!empty($update_files))
		{
			if (!$reverse)
				ksort($update_files, SORT_NUMERIC);
			else
				krsort($update_files, SORT_NUMERIC);
		}

		return $update_files;
	}

	/**
	 * Output wrapper
	 *
	 * @param string $message
	 * @param integer $level		1 = gewoon (altijd tonen), 2 = debugging (standaard verbergen)
	 */
	protected function log($message, $level = 1)
	{
		if ($level > 2)
			return;

		if (is_array($message))
		{
			if (count($message) == 0)
				return;

			$message = implode(PHP_EOL, $message);
		}

		echo $message . PHP_EOL;

		if ($this->logfile)
			error_log($message . PHP_EOL, 3, $this->logfile);
	}

	/**
	 * Zet het array van rsync excludes om in een lijst rsync parameters
	 *
	 * @returns string
	 */
	protected function prepareExcludes()
	{
		$this->log('prepareExcludes', 2);

		chdir($this->basedir);

		$exclude_param = '';

		if (count($this->rsync_excludes) > 0)
		{
			foreach ($this->rsync_excludes as $exclude)
			{
				if (!file_exists($exclude))
					throw new DeployException('Rsync exclude file niet gevonden: '. $exclude);

				$exclude_param .= '--exclude-from='. escapeshellarg($exclude) .' ';
			}
		}

		if (!empty($this->data_dirs))
		{
			foreach ($this->data_dirs as $data_dir)
			{
				$exclude_param .= '--exclude '. escapeshellarg("/$data_dir") .' ';
			}
		}

		return $exclude_param;
	}

	/**
	 * Bereidt de --link-dest parameter voor rsync voor als dat van toepassing is
	 *
	 * @returns string
	 */
	protected function prepareLinkDest($remote_dir)
	{
		$this->log('prepareLinkDest', 2);

		if ($remote_dir === null)
			$remote_dir = $this->remote_dir;

		$linkdest = '';

		if ($this->last_remote_target_dir)
		{
			$linkdest = "--copy-dest=$remote_dir/{$this->last_remote_target_dir}";
		}

		return $linkdest;
	}

	protected function prepareRemoteDirectory($remote_host, $remote_dir = null)
	{
		if ($remote_dir === null)
			$remote_dir = $this->remote_dir;

		$output = array();
		$return = null;
		$this->sshExec($remote_host, "mkdir -p $remote_dir", $output, $return); // | grep '{$this->project_name}_'

		if (!empty($this->data_dirs))
		{
			$data_dirs = count($this->data_dirs) > 1 ? '{'. implode(',', $this->data_dirs) .'}' : implode(',', $this->data_dirs);

			$cmd = "mkdir -v -m 0775 -p $remote_dir/{$this->data_dir_prefix}/$data_dirs";

			$output = array();
			$return = null;
			$this->sshExec($remote_host, $cmd, $output, $return);
		}
	}

	/**
	 * Geeft de timestamps van de voorlaatste en laatste deployments terug
	 *
	 * @returns array [previous_timestamp, last_timestamp]
	 */
	protected function findPastDeploymentTimestamps($remote_host, $remote_dir = null)
	{
		$this->log('findPastDeploymentTimestamps', 2);

		$this->prepareRemoteDirectory($remote_host, $remote_dir);

		if ($remote_dir === null)
			$remote_dir = $this->remote_dir;

		$dirs = array();
		$return = null;
		$this->sshExec($remote_host, "ls -1 $remote_dir", $dirs, $return);

		if ($return !== 0) {
			throw new DeployException('ssh initialize failed');
		}

		$deployment_timestamps = array();

		if (count($dirs))
		{
			foreach ($dirs as $dirname)
			{
				if (
					preg_match('/'. preg_quote($this->project_name) .'_\d{4}-\d{2}-\d{2}_\d{6}/', $dirname) &&
					($time = strtotime(str_replace(array($this->project_name .'_', '_'), array('', ' '), $dirname)))
				   )
				{
					$deployment_timestamps[] = $time;
				}
			}

			$count = count($deployment_timestamps);

			if ($count > 0)
			{
				sort($deployment_timestamps);

				$this->log($dirs);

				if ($count >= 2)
					return array_slice($deployment_timestamps, -2);

				return array(null, array_pop($deployment_timestamps));
			}
		}

		return array(null, null);
	}

	/**
	 * Geeft een array terug van alle oude deployments die verwijderd mogen worden.
	 *
	 * @returns array
	 */
	protected function collectPastDeployments($remote_host, $remote_dir)
	{
		$this->log('collectPastDeployments', 2);

		$dirs = array();
		$return = null;
		$this->sshExec($remote_host, "ls -1 $remote_dir", $dirs, $return);

		if ($return !== 0) {
			throw new DeployException('ssh initialize failed');
		}

		if (count($dirs))
		{
			$deployment_dirs = array();

			foreach ($dirs as $dirname)
			{
				if (preg_match('/'. preg_quote($this->project_name) .'_\d{4}-\d{2}-\d{2}_\d{6}/', $dirname))
				{
					$deployment_dirs[] = $dirname;
				}
			}

			// de laatste twee deployments blijven altijd staan
			if (count($deployment_dirs) > 2)
			{
				$dirs_to_delete = array();

				sort($deployment_dirs);

				$deployment_dirs = array_slice($deployment_dirs, 0, -2);

				foreach ($deployment_dirs as $key => $dirname)
				{
					$time = strtotime(str_replace(array($this->project_name .'_', '_'), array('', ' '), $dirname));

					// alles ouder dan een maand mag sowieso weg
					if ($time < strtotime('-1 month')) {
						$this->log("$dirname is ouder dan een maand");

						$dirs_to_delete[] = $dirname;
					}

					// alles ouder dan een week alleen de laatste van de dag bewaren
					elseif ($time < strtotime('-1 week'))
					{
						if (isset($deployment_dirs[$key+1]))
						{
							$time_next = strtotime(str_replace(array($this->project_name .'_', '_'), array('', ' '), $deployment_dirs[$key+1]));

							// als de volgende deployment op dezelfde dag was kan deze weg
							if (date('j', $time_next) == date('j', $time))
							{
								$this->log("$dirname werd opgevolgd diezelfde dag");

								$dirs_to_delete[] = $dirname;
							}
							else
							{
								$this->log("$dirname blijft staan");
							}
						}
					}

					else
					{
						$this->log("$dirname blijft staan");
					}
				}

				return $dirs_to_delete;
			}
		}
	}

	/**
	 * Voert de verwijdering van collectPastDeployments resultaten uit
	 *
	 * @param array $past_deployments
	 */
	protected function deletePastDeployments($past_deployments)
	{
		foreach ($past_deployments as $past_deployment)
		{
			foreach ($past_deployment['dirs'] as $dir)
			{
				$this->rollbackFiles($past_deployment['remote_host'], $past_deployment['remote_dir'], $dir);
			}
		}
	}

	/**
	 * Wrapper voor SSH commando's
	 *
	 * @param string $remote_host
	 * @param string $command
	 * @param array $output
	 * @param int $return
	 * @param string $hide_pattern		Regex waarmee de output kan worden gekuisd (voor wachtwoorden bv.)
	 * @param string $hide_replacement
	 */
	protected function sshExec($remote_host, $command, &$output, &$return, $hide_pattern = '', $hide_replacement = '')
	{
		$cmd = $this->ssh_path .' '. $this->remote_user .'@'. $remote_host .' "'. str_replace('"', '\"', $command) .'"';

		if ($hide_pattern != '')
			$show_cmd = preg_replace($hide_pattern, $hide_replacement, $cmd);
		else
			$show_cmd = $cmd;

		$this->log('sshExec: '. $show_cmd);

		exec($cmd, $output, $return);
	}

    /**
     * Wrapper voor rsync commando's
     *
     * @param string $command
     * @param string $error_msg
     * @throws DeployException
     */
	protected function rsyncExec($command, $error_msg = 'Rsync is mislukt')
	{
		$this->log('execRSync: '. $command, 2);

		chdir($this->basedir);

		passthru($command, $return);

		if ($return !== 0) {
			throw new DeployException($error_msg);
		}
	}

	/**
	 * Vraagt om invoer van de gebruiker
	 *
	 * @param string $message
	 * @param string $default
	 * @param boolean $isPassword
	 * @return string
	 */
	static protected function inputPrompt($message, $default = '', $isPassword = false)
	{
		fwrite(STDOUT, $message);

		if (!$isPassword)
		{
			$input = trim(fgets(STDIN));
		}
		else
		{
			$input = self::getPassword(false);
			echo PHP_EOL;
		}

		if ($input == '')
			$input = $default;

		return $input;
	}

	/**
	 * Stub methode voor extra uitbreidingen die *voor* deploy worden uitgevoerd
	 */
	protected function preDeploy($remote_host, $remote_dir, $target_dir)
	{
		$this->log("preDeploy($remote_host, $remote_dir, $target_dir)", 2);
	}

	/**
	 * Stub methode voor extra uitbreidingen die *na* deploy worden uitgevoerd en *voor* de cache clears
	 */
	protected function postDeploy($remote_host, $remote_dir, $target_dir)
	{
		$this->log("postDeploy($remote_host, $remote_dir, $target_dir)", 2);

		$this->restartGearmanWorkers($remote_host, $remote_dir, $target_dir);
	}

	/**
	 * Stub methode voor extra uitbreidingen die *voor* rollback worden uitgevoerd
	 */
	protected function preRollback($remote_host, $remote_dir, $target_dir)
	{
		$this->log("preRollback($remote_host, $remote_dir, $target_dir)", 2);
	}

	/**
	 * Stub methode voor extra uitbreidingen die *na* rollback worden uitgevoerd
	 */
	protected function postRollback($remote_host, $remote_dir, $target_dir)
	{
		$this->log("postRollback($remote_host, $remote_dir, $target_dir)", 2);

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
