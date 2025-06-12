<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\ServerInfoInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\DBAL\Connection;
// PHP
use DateTimeImmutable;

#[AsAlias(ServerInfoInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class ServerInfo implements ServerInfoInterface
{
    use TraitBaseService;

    private readonly array $symfony;
    private readonly array $php;
    private readonly array $db;


    public function __construct(
        public readonly KernelInterface $kernel,
        #[Autowire(service: 'doctrine.dbal.default_connection')]
        public readonly Connection $connection
    ) {}

    /**
     * get Symfony info
     * @return mixed
     */
    public function getSymfonyInfo(?string $name = null): mixed
    {
        if(!isset($this->symfony)) {
            /** @var App/Kernel $kernel */
            $kernel = $this->kernel;
            $eom = explode('/', $kernel::END_OF_MAINTENANCE);
            $END_OF_MAINTENANCE = new DateTimeImmutable($eom[1].'-'.$eom[0].'-01');
            $eol = explode('/', $kernel::END_OF_LIFE);
            $END_OF_LIFE = new DateTimeImmutable($eol[1].'-'.$eol[0].'-01');
            $this->symfony = [
                'VERSION' => $kernel::VERSION,
                'SHORT_VERSION' => $kernel::MAJOR_VERSION.'.'.$kernel::MINOR_VERSION,
                'VERSION_ID' => $kernel::VERSION_ID,
                'MAJOR_VERSION' => $kernel::MAJOR_VERSION,
                'MINOR_VERSION' => $kernel::MINOR_VERSION,
                'RELEASE_VERSION' => $kernel::RELEASE_VERSION,
                'EXTRA_VERSION' => $kernel::EXTRA_VERSION,
                'END_OF_MAINTENANCE' => $END_OF_MAINTENANCE,
                'END_OF_MAINTENANCE_TEXT' => $END_OF_MAINTENANCE->format('d/m/Y'),
                'END_OF_LIFE' => $END_OF_LIFE,
                'END_OF_LIFE_TEXT' => $END_OF_LIFE->format('d/m/Y'),
            ];
        }
        return empty($name) ? $this->symfony : $this->symfony[$name] ?? null;
    }

    /**
     * get PHP info
     * @return mixed
     */
    public function getPhpInfo(?string $param = null): mixed
    {
        if(!isset($this->php)) {
            // PHP INFO / in MB : memory_get_usage() / 1048576
            $this->php = [
                'VERSION' => phpversion(),
                'PHP_VERSION_ID' => PHP_VERSION_ID,
                'PHP_EXTRA_VERSION' => PHP_EXTRA_VERSION,
                'PHP_MAJOR_VERSION' => PHP_MAJOR_VERSION,
                'PHP_MINOR_VERSION' => PHP_MINOR_VERSION,
                'PHP_RELEASE_VERSION' => PHP_RELEASE_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'date.timezone' => ini_get('date.timezone'),
            ];
        }
        return empty($param) ? $this->php : $this->php[$param] ?? [];
    }

    /**
     * get DATABASE info
     * 
     * "use_savepoints" => true
     * "driver" => "pdo_mysql"
     * "idle_connection_ttl" => 600
     * "host" => "database"
     * "port" => 3306
     * "user" => "root"
     * "password" => "password"
     * "driverOptions" => []
     * "defaultTableOptions" => []
     * "dbname" => "appdata"
     * "serverVersion" => "10.6.22-MariaDB"
     * "charset" => "utf8mb4"
     * 
     * @return mixed
     */
    public function getDatabaseInfo(?string $name = null): mixed
    {
        $this->db ??= $this->connection->getParams();
        return empty($name) ? $this->db : $this->db[$name] ?? null;
    }


}