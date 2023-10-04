<?php

namespace ArsDigitalia;

class RepositoryConfig {

    protected $config = [];

    public function __construct(array $configs = []) {
        foreach ($configs as $key => $config) {
            $this->set($key, $config);
        }
    }

    public function set(string $key, $value) : bool {
        $config = &$this->config;
        foreach (explode('.', $key) as $k) {
            $config = &$config[$k];
        }
        $config = $value;
        return true;
    }

    public function get(string $key, $default = null) : string {
        $config = $this->config;
        foreach (explode('.', $key) as $k) {
            if (! isset($config[$k])) {
                return $default;
            }
            $config = $config[$k];
        }
        return $config;
    }

}
