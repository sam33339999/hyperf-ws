PATH := bin:$(PATH)

run:
	swoole-cli -d swoole.use_shortname=Off bin/hyperf.php start

hot-reload:
	swoole-cli -d swoole.use_shortname=Off bin/hyperf-hot-restart-in-dev.php start