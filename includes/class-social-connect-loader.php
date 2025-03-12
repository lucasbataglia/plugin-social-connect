<?php

/**
 * Registra todos os actions e filters do plugin.
 */
class Social_Connect_Loader {

    /**
     * Array de actions registradas com WordPress.
     */
    protected $actions;

    /**
     * Array de filters registrados com WordPress.
     */
    protected $filters;

    /**
     * Inicializa as coleções.
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Adiciona uma nova action ao array de actions registradas.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Adiciona um novo filter ao array de filters registrados.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Utility para registrar hooks na coleção apropriada.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Registra os hooks com WordPress.
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}