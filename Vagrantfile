Vagrant.configure(2) do |config|
    # Base box
    config.vm.box = "ubuntu/artful64"

    # Port forwardings
    config.vm.network "forwarded_port", guest: 80, host: 9001, host_ip: "127.0.0.1"

    # Virtual machine details
    config.vm.provider "virtualbox" do |vb|
        vb.gui = false
        vb.cpus = 4
        vb.memory = 2048
        vb.name = "YBoard"
        vb.customize [ "modifyvm", :id, "--uartmode1", "disconnected" ]
    end

    # Provisioning
    config.vm.provision "shell", path: "vagrant_provision.sh"
end
