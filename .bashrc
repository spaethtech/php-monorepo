#!/usr/bin/env bash
# shellcheck disable=SC1090,SC2139,SC2046

if [[ "$(uname -s)" != MINGW* ]]
then
    echo "This .bashrc file is exclusively for Windows development in Git Bash!"
    exit
fi

# Determine the Project's directory in relation to this script.
SCRIPTS_DIR=$(dirname -- "$(readlink -f "${BASH_SOURCE[0]}")")
PROJECT_DIR=$(realpath "$(cd -- "$SCRIPTS_DIR" &> /dev/null && pwd)"/.)
PROJECT_BIN=$PROJECT_DIR/bin

# ------------------------------------------------------------------------------
# Helper Functions
# ------------------------------------------------------------------------------

# Prepends a directory to PATH only if it exists AND is not already included.
path_prepend_exists() {
    if [ -d "$1" ] && [[ ":$PATH:" != *":$1:"* ]]
    then
        PATH="$1${PATH:+":$PATH"}"
    fi
}

# Prepends a directory to PATH regardless of its existence.
path_prepend() {
    if [[ ":$PATH:" != *":$1:"* ]]
    then
        PATH="$1${PATH:+":$PATH"}"
    fi
}

# Appends a directory to PATH only if it exists AND is not already included.
path_append_exists() {
    if [ -d "$1" ] && [[ ":$PATH:" != *":$1:"* ]]
    then
        PATH="${PATH:+"$PATH:"}$1"
    fi
}

# Appends a directory to PATH regardless of its existence.
path_append() {
    if [[ ":$PATH:" != *":$1:"* ]]
    then
        PATH="${PATH:+"$PATH:"}$1"
    fi
}

command_exists() {
  command -v "$1" &> /dev/null
}

# ------------------------------------------------------------------------------
# PHP & Composer
# ------------------------------------------------------------------------------

# If you already have PHP in your PATH, this can be safely removed.  However, if
# you do would like to override the system-wide PHP interpreter in favor of a
# pre-project one, set this to the appropriate directory.
#path_prepend $(cygpath "C:\dev\lib\php\7.4.27-nts")
path_prepend $(cygpath "C:\dev\lib\php\8.1.11-nts")

# If you already have Composer in your PATH, this can be safely removed.  The
# same information from above applies here.
path_prepend $(cygpath "C:\dev\lib\composer")

# ------------------------------------------------------------------------------
# Aliases
# ------------------------------------------------------------------------------

# Load any project command aliases...
if [ -f "$PROJECT_DIR"/.bash_aliases ]; then
    shopt -s expand_aliases
    source "$PROJECT_DIR"/.bash_aliases
fi

# ------------------------------------------------------------------------------
# Dependencies
# ------------------------------------------------------------------------------

# Global Composer
COMPOSER_HOME=$(cygpath "$APPDATA")/Composer
path_append "$COMPOSER_HOME/vendor/bin"

# Project Composer
if [ -f "$PROJECT_DIR"/composer.json ] && [ ! -d "$PROJECT_DIR"/vendor ]
then
    (cd "$PROJECT_DIR" && composer install)
fi
path_append_exists "$PROJECT_DIR/vendor/bin"

# Global NPM
NPM_HOME=$(cygpath "$APPDATA")/npm
path_append "$NPM_HOME/node_modules"

# Local NPM
if [ -f "$PROJECT_DIR"/package.json ] && [ ! -d "$PROJECT_DIR"/node_modules ]
then
    (cd "$PROJECT_DIR" && composer update)
fi
path_append_exists "$PROJECT_DIR/vendor/bin"

# Project binaries
path_append "$PROJECT_DIR/bin"

# ------------------------------------------------------------------------------
# Environment
# ------------------------------------------------------------------------------

# NOTE: VIRTUAL_ENV should be set on any guest (via /etc/environment), so this
# is the best way to determine whether we're on the host.  It should work with
# any host (i.e. Windows, Darwin, Linux, etc)!
VIRTUAL_ENV=${VIRTUAL_ENV:-host}

# Export the project directory for use in the terminal.
export PROJECT_DIR=$PROJECT_DIR
export PROJECT_BIN=$PROJECT_BIN
export VIRTUAL_ENV=$VIRTUAL_ENV

# shellcheck disable=SC2164
cd "$PROJECT_DIR"

HISTFILE="$PROJECT_DIR"/.bash_history
HISTSIZE=100
HISTFILESIZE=100
PROMPT_COMMAND="history -a"
