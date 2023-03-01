#!/usr/bin/env bash

if [[ "$(uname -s)" != MINGW* ]]; then
    echo "This .bashrc file is only designed to work with Git Bash on Windows, exiting!"
fi

PHP_PATH=$(cygpath "C:\dev\lib\php\7.4.27-nts")
COMPOSER_PATH=$(cygpath "C:\dev\lib\composer")

#alias php74=$(cygpath "C:\dev\lib\php\7.4.27-nts\php.exe")
#alias php81=$(cygpath "C:\dev\lib\php\8.1.11-nts\php.exe")
#alias php82=$(cygpath "C:\dev\lib\php\8.2.1-nts\php.exe")

# Function to prepend a directory to the PATH only if it exists AND is not already included.
path_prepend_exists() {
    if [ -d "$1" ] && [[ ":$PATH:" != *":$1:"* ]]; then
        PATH="$1${PATH:+":$PATH"}"
    fi
}

path_prepend() {
    if [[ ":$PATH:" != *":$1:"* ]]; then
        PATH="$1${PATH:+":$PATH"}"
    fi
}

# Function to append a directory to the PATH only if it exists AND is not already included.
path_append_exists() {
    if [ -d "$1" ] && [[ ":$PATH:" != *":$1:"* ]]; then
        PATH="${PATH:+"$PATH:"}$1"
    fi
}

path_append() {
    if [[ ":$PATH:" != *":$1:"* ]]; then
        PATH="${PATH:+"$PATH:"}$1"
    fi
}

win_path() {
    local path
    path=$(sed -E "s/([A-Za-z]):/\/\L\1/g" <<< "${1//\\//}")
    echo "$path"
}

# Determine the Project's directory in relation to this script.
PROJECT_DIR=$(realpath "$( cd -- "$( dirname -- "$(readlink -f "${BASH_SOURCE[0]}")" )" &> /dev/null && pwd )"/.)
DEV_BIN_DIR=$PROJECT_DIR/bin

if [ ! -d "$PROJECT_DIR"/vendor ]
then
    (cd "$PROJECT_DIR" && composer update)
fi

# Load any user-defined command aliases
if [ -f "$PROJECT_DIR"/.bash_aliases ]; then
    # shellcheck source=./.bash_aliases
    source "$PROJECT_DIR"/.bash_aliases
fi

# NOTE: The ENVIRONMENT should already be set on the guest (via /etc/environment), so this is the best way to determine
# whether we're on the host.  It should work with any host (i.e. Windows, Darwin, Linux, etc)!
#VIRTUAL_ENV=${VIRTUAL_ENV:-host}

#COMPOSER_HOME=$(composer -n config --global home 2> /dev/null)
#COMPOSER_HOME=${COMPOSER_HOME//C://c}
#COMPOSER_HOME=$(win_path "$APPDATA")/Composer
#NPM_HOME=$(win_path "$APPDATA")/npm
COMPOSER_HOME=$(cygpath "$APPDATA")/Composer
NPM_HOME=$(cygpath "$APPDATA")/npm

# Set any directories to be added to $PATH...
#path_append "$COMPOSER_HOME/vendor/bin"
#path_append "$(which npm 2> /dev/null)"/vendor/bin
path_append "$PHP_PATH"
path_append "$COMPOSER_PATH"
path_append "$COMPOSER_HOME/vendor/bin"
path_append "$NPM_HOME/node_modules"
path_append "$PROJECT_DIR/vendor/bin"
path_append "$PROJECT_DIR/bin"

# Export the project directory for use in the terminal.
export PROJECT_DIR=$PROJECT_DIR
export DEV_BIN_DIR=$DEV_BIN_DIR
#export VIRTUAL_ENV=$VIRTUAL_ENV

# shellcheck disable=SC2164
cd "$PROJECT_DIR"


#[ -f .bash_aliases ] && source .bash_aliases

# shellcheck disable=SC1090
#[ -f ~/.bash_aliases ] && source ~/.bash_aliases
