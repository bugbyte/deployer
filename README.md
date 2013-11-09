# Installation

The easiest way is to use Composer:

    {
        "require": {
            "bugbyte/deployer": "1.0.*"
        }
    }

If you can't or don't want to use Composer (oh come on..) you can just download from GitHub, but you'll have to take care of loading the files by yourself.

# Usage

See [example.php](https://github.com/bugbyte/deployer/blob/master/example.php) for a simple setup.

# Requirements

- Linux or FreeBSD (on both the local and the remote server)
- SSH & RSync access to the remote server, preferably with public key auth (or you'll be typing your password **a lot**.
- PHP CLI 5.2+ (on both the local and remote servers)

# Workings

First, rsync is used to upload files to the remote server. Aside from supplying your own exclude files, data_dirs are excluded automatically.  
If previous deployments are found the latest is used as --copy-dest to speed up rsync, and the new deployment is uploaded next to it in a new directory.
At first deploy any directories with User Generated Content (data_dirs) are moved outside of the project root:
> by running "mv" in the remote shell  
> by running "ln -s" symlinks are created in their place (you may need to activate Apache's FollowSymlinks option)

Target specific files are renamed ("mv again") after everything is uploaded. These moved files will appear as changed/missing files in subsequent deploys.  
After all file-stuff is done, a symlink called "production" is created, pointing to the deployment directory. This is your DocumentRoot.

There are several stub methods in place to allow you to easily hook custom code into some steps of the deployment process.
- preDeploy
- postDeploy
- preRollback
- postRollback
- clearRemoteCaches

In case of trouble, you can rollback to the previous deploy in only a few seconds.  
The "production"-symlink is changed back and the new deployment is deleted.

This tool can also handle database updates. The current way is rather primitive however, relying solely on deployment timestamps. When branching and merging often this becomes cumbersome quickly.  

In the databasemanager branch (currently being developed at [LemonWeb](https://github.com/LemonWeb/deployer/tree/databasemanager)) a new way using a patch registry within the database is being implemented. This will be released as version 2.x when it's finished.

# Todo

- consistent coding style
- finish translating everything from Dutch to English
- clean up the output during deployment a bit more
