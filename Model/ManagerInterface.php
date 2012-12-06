<?php
namespace Kachkaev\PostgresHelperBundle\Model;

interface ManagerInterface
{
    public function listNames();

    public function updateList();
    
    public function has($name);
    
    public function init($name);

    public function rename($oldName, $newName);
    
    public function get($name);
    
    public function delete($name);
}