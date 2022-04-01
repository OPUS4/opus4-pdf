# -*- mode: ruby -*-
# vi: set ft=ruby :

$software = <<SCRIPT
# Downgrade to PHP 7.1
apt-add-repository -y ppa:ondrej/php
apt-get -yq update
apt-get -yq install php7.1

# Install required PHP packages
apt-get -yq install php7.1-curl
apt-get -yq install php7.1-dom

# Install required tools
apt-get -yq install texlive-xetex
apt-get -yq install pandoc
apt-get -yq install ant
apt-get -yq install unzip
SCRIPT

$composer = <<SCRIPT
cd /vagrant
bin/install-composer.sh
bin/composer update
SCRIPT

$xdebug = <<SCRIPT
apt-get -yq install php7.1-xdebug
if ! grep "xdebug.mode=debug" /etc/php/7.1/mods-available/xdebug.ini > /dev/null; then
  echo -e "xdebug.mode=debug\nxdebug.client_host=10.0.2.2\nxdebug.client_port=9003" >> /etc/php/7.1/mods-available/xdebug.ini
fi
SCRIPT

$pandoc = <<SCRIPT
# Install newer 'pandoc' version
cd /home/vagrant
wget https://github.com/jgm/pandoc/releases/download/2.17.1.1/pandoc-2.17.1.1-1-amd64.deb
dpkg -i pandoc-2.17.1.1-1-amd64.deb
SCRIPT

$fonts = <<SCRIPT
# Install "Open Sans" font family (available under the Apache License v.2.0 at https://fonts.google.com/specimen/Open+Sans)
# to be used for PDF cover generation by templates in test/Cover/PdfGenerator/_files/covers
mkdir -p /usr/share/fonts/opentype
cd /home/vagrant
wget https://fonts.google.com/download?family=Open%20Sans -O Open_Sans.zip
unzip -o Open_Sans.zip -d Open_Sans
cp -r /home/vagrant/Open_Sans/static/OpenSans/ /usr/share/fonts/opentype/
apt-get -yq install fontconfig
fc-cache -f -v
SCRIPT

$environment = <<SCRIPT
if ! grep "cd /vagrant" /home/vagrant/.profile > /dev/null; then
  echo "cd /vagrant" >> /home/vagrant/.profile
fi
if ! grep "PATH=/vagrant/bin" /home/vagrant/.bashrc > /dev/null; then
  echo "export PATH=/vagrant/bin:$PATH" >> /home/vagrant/.bashrc
fi
if ! grep "XDEBUG_SESSION=OPUS4" /home/vagrant/.bashrc > /dev/null; then
  echo "export XDEBUG_SESSION=OPUS4" >> /home/vagrant/.bashrc
fi
SCRIPT

$help = <<SCRIPT
echo "Use 'vagrant ssh' to log into VM and 'logout' to leave it."
echo "In VM use:"
echo "'composer test' for running tests"
echo "'composer update' to update dependencies"
echo "'composer cs-check' to check coding style"
echo "'composer cs-fix' to automatically fix basic style problems"
SCRIPT

Vagrant.configure("2") do |config|
  config.vm.box = "bento/ubuntu-20.04"

  config.vm.provision "Install required software...", type: "shell", inline: $software
  config.vm.provision "Install Xdebug...", type: "shell", inline: $xdebug
  config.vm.provision "Install pandoc...", type: "shell", inline: $pandoc
  config.vm.provision "Install fonts...", type: "shell", inline: $fonts
  config.vm.provision "Install Composer dependencies...", type: "shell", privileged: false, inline: $composer
  config.vm.provision "Setup environment...", type: "shell", inline: $environment
  config.vm.provision "Information", type: "shell", privileged: false, run: "always", inline: $help
end
