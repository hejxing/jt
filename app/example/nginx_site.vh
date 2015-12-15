# csmall2
# 请自行根据实际情况修改

server {
	listen 80;
	server_name example.dev.csmall.com;

	expires -1s;

	location / {
		include fcgi.conf;
		#fastcgi_pass 127.0.0.1:9000; #此处已放入 fcgi.conf
		fastcgi_index index;
		fastcgi_param  SCRIPT_FILENAME    /web/jt/app/example/index_develop.php;
	}

	location /static {
		root /web/jt/app/example;
	}

	location ~* \.htm$ {
		root /web/jt/app/example/static/html;
	}
}

