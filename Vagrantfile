# -*- mode: ruby -*-
# vi: set ft=ruby :

require "json"
require 'rbconfig'

SSL_PATH = File.expand_path("~/.ssh/id_rsa")
UISP_VERSION = "1.4.2"
PLUGIN_MANIFEST = "./example/src/manifest.json"

Vagrant.configure("2") do |config|

    if (File.file?(PLUGIN_MANIFEST))
        file = File.read(PLUGIN_MANIFEST)
        json = JSON.parse(file)
        PLUGIN_NAME = json["information"]["name"] ||= "example"
    else
        PLUGIN_NAME = "example"
    end

    #config.vm.name = "vagrant"
    config.vm.hostname = "vagrant.spaethtech.com"


    # Create a forwarded port mapping which allows access to a specific port
    # within the machine from a port on the host machine. In the example below,
    # accessing "localhost:8080" will access port 80 on the guest machine.
    # NOTE: This will enable public access to the opened port
    # config.vm.network "forwarded_port", guest: 80, host: 8080

    # Create a forwarded port mapping which allows access to a specific port
    # within the machine from a port on the host machine and only allow access
    # via 127.0.0.1 to disable public access

    config.vm.network "forwarded_port", guest: 80, host: 80, host_ip: "127.0.0.1"
    config.vm.network "forwarded_port", guest: 443, host: 443, host_ip: "127.0.0.1"
    config.vm.network "forwarded_port", guest: 8022, host: 8022, host_ip: "127.0.0.1"
    config.vm.network "forwarded_port", guest: 5432, host: 5432, host_ip: "127.0.0.1"
    config.vm.network "forwarded_port", guest: 9003, host: 9003, host_ip: "127.0.0.1"

    # Create a private network, which allows host-only access to the machine
    # using a specific IP.
    #config.vm.network "private_network", ip: "192.168.33.10"

    # Create a public network, which generally matched to bridged network.
    # Bridged networks make the machine appear as another physical device on
    # your network.
    # config.vm.network "public_network"

    # Share an additional folder to the guest VM. The first argument is
    # the path on the host to the actual folder. The second argument is
    # the path on the guest to mount the folder. And the optional third
    # argument is a set of non-required options.

    # Disable the default synced folder.
    config.vm.synced_folder ".", "/vagrant", disabled: true

    # Add the synced folder for our application overrides.
    config.vm.synced_folder "./unms/app", "/home/unms/app",
        type: "rsync",
        rsync__args: ["-r", "--include=docker-compose.override.yml", "--exclude=*"],
        owner: 1001,
        group: "root",
        create: true

    # Add the synced folder for our xdebug docker image.
    config.vm.synced_folder "./unms/app/upm/", "/home/unms/app/upm/",
        type: "rsync",
        mount_options: [ "dmode=755,fmode=755" ],
        owner: 1001,
        group: "root",
        create: true

    # Add the synced folder for our xdebug docker image.
    config.vm.synced_folder "./unms/app/xdebug/", "/home/unms/app/xdebug/",
        type: "rsync",
        #mount_options: [ "dmode=777,fmode=755" ],
        owner: 1001,
        group: "root",
        create: true



    # DigitalOcean Droplet Configuration
    config.vm.provider :digital_ocean do |provider, override|

        # Generate the necessary RSA key pair, if they do not already exist!
        if (! File.file?(SSL_PATH))
            #puts "FOUND!\n"
        #else
            puts "SSL key pair not found...\n"
            puts %x( ssh-keygen -t rsa -b 4096 -f #{SSL_PATH} -N "" )
        end

        override.ssh.private_key_path = SSL_PATH
        override.vm.box = "digital_ocean"
        override.vm.box_url = "https://github.com/devopsgroup-io/vagrant-digitalocean/raw/master/box/digital_ocean.box"
        override.nfs.functional = false
        override.vm.allowed_synced_folder_types = :rsync
        provider.ssh_key_name = "vagrant"
        provider.token = "1b714c7a635cec4d0afdd280b16325d51df67c0d6a36f9de31b15ecbc71878bc"
        provider.image = "ubuntu-20-04-x64"
        provider.region = "sfo3"
        provider.size = "s-1vcpu-2gb"
        provider.backups_enabled = false
        provider.private_networking = false
        provider.ipv6 = false
        provider.monitoring = false
        provider.tags = "ucrm-plugins"
        provider.name = "vagrant.spaethtech.com"
    end


    #config.vm.provision :docker

    # vagrant plugin install vagrant-docker-compose
    # 1.23.2?
    #config.vm.provision :docker_compose

    # Enable provisioning with a shell script. Additional provisioners such as
    # Ansible, Chef, Docker, Puppet and Salt are also available. Please see the
    # documentation for more information about their specific syntax and use.
    config.vm.provision "shell", keep_color: true, inline: <<-SHELL
        echo "Disabling IPv6..."
        echo "net.ipv6.conf.all.disable_ipv6 = 1" >> /etc/sysctl.conf
        echo "net.ipv6.conf.default.disable_ipv6 = 1" >> /etc/sysctl.conf
        echo "net.ipv6.conf.lo.disable_ipv6 = 1" >> /etc/sysctl.conf
        echo "net.ipv6.conf.eth0.disable_ipv6 = 1" >> /etc/sysctl.conf
        sysctl -p

        curl -fsSL https://uisp.ui.com/v1/install > /tmp/uisp_inst.sh
        bash /tmp/uisp_inst.sh --version #{UISP_VERSION}

        #cd /home/unms/app && docker-compose -p unms up -d
    SHELL



    config.vm.provision "shell", keep_color: true, run: "always", inline: <<-SHELL
        #cd /home/unms/app
        #./xdebug/build.sh
        # sudo docker-compose -p unms up -d --build



    SHELL

end
