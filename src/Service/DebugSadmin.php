<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Attribute\DebugToOptimize;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\DebugSadminInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Files;
use Aequation\WireBundle\Tools\Objects;
use Aequation\WireBundle\Tools\Strings;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias(DebugSadminInterface::class, public: false)]
#[Autoconfigure(autowire: true, lazy: true)]
class DebugSadmin implements DebugSadminInterface
{
    use TraitBaseService;

    // public const SEARCH_IN_PHP_FILE_METHOD = 1; // 0: regex, 1: token_get_all

    public function __construct(
        public readonly AppWireServiceInterface $appWire,
    ) {
        // Initialization code if needed
    }

    #[DebugToOptimize(type: 'warning', description: 'Toutes les classes ne sont pas chargées, il faudrait lire les namespaces de tous les fichiers plutôt que rechercher avec get_declared_classes().')]
    public function getToOptimize(): array
    {
        // $searchs = [
        //     $this->appWire->getProjectDir('src'),
        //     $this->appWire->getProjectDir('vendor/aequation/wire/src'),
        // ];
        // $class_files = [];
        // foreach ($searchs as $search) {
        //     foreach (Files::findPhpFiles($search, "namespace") as $finder) {
        //         dd(static::extractPhpClasses($finder->getPathname()));
        //         $class_files[] = static::getClassnameFromFile($finder->getPathname());
        //     }
        // }
        // dd($class_files);

        $listOfClasses = '#^(Aequation|App)\\\#';
        Objects::filterDeclaredClasses($listOfClasses, true);
        // dump($listOfClasses);
        $opts = [];
        /** @var array $listOfClasses */
        foreach($listOfClasses as $class) {
            $opts = array_merge($opts, Objects::getMethodAttributes($class, DebugToOptimize::class));
        }
        return $opts;
    }

    /**
     * Extract PHP classes from a file using token_get_all
     * @see https://www.php.net/manual/en/function.token-get-all.php
     * @see https://stackoverflow.com/questions/7153000/get-class-name-from-file
     */

    // public static function extractPhpClasses(string $path)
    // {
    //     $code = file_get_contents($path);
    //     $tokens = @token_get_all($code);
    //     $namespace = $class = $classLevel = $level = NULL;
    //     $classes = [];
    //     while (list(, $token) = each($tokens)) {
    //         switch (is_array($token) ? $token[0] : $token) {
    //             case T_NAMESPACE:
    //                 $namespace = ltrim(static::fetch($tokens, [T_STRING, T_NS_SEPARATOR]) . '\\', '\\');
    //                 break;
    //             case T_CLASS:
    //             case T_INTERFACE:
    //                 if ($name = static::fetch($tokens, T_STRING)) {
    //                     $classes[] = $namespace . $name;
    //                 }
    //                 break;
    //         }
    //     }
    //     return $classes;
    // }

    // private static function fetch(&$tokens, $take)
    // {
    //     $res = NULL;
    //     while ($token = current($tokens)) {
    //         list($token, $s) = is_array($token) ? $token : [$token, $token];
    //         if (in_array($token, (array) $take, TRUE)) {
    //             $res .= $s;
    //         } elseif (!in_array($token, [T_DOC_COMMENT, T_WHITESPACE, T_COMMENT], TRUE)) {
    //             break;
    //         }
    //         next($tokens);
    //     }
    //     return $res;
    // }


    // public static function getClassnameFromFile(string $file): string
    // {
    //     if(static::SEARCH_IN_PHP_FILE_METHOD === 0) {
    //         // Using regex to find the class name
    //         $fp = fopen($file, 'r');
    //         $class = $buffer = '';
    //         $i = 0;
    //         while (!$class) {
    //             if (feof($fp)) break;
    //             $buffer .= fread($fp, 512);
    //             if (preg_match('/namespace\s+(\w+)(.*)?\{/', $buffer, $matches)) {
    //                 $class = $matches[1];
    //                 break;
    //             }
    //         }
    //     } else {
    //         // Using token_get_all to find the class name
    //         $fp = fopen($file, 'r');
    //         $class = $namespace = $buffer = '';
    //         $i = 0;
    //         while (!$class) {
    //             if (feof($fp)) break;
    //             $buffer .= fread($fp, 512);
    //             $tokens = token_get_all($buffer);
    //             if (strpos($buffer, '{') === false) continue;
    //             for (;$i<count($tokens);$i++) {
    //                 if ($tokens[$i][0] === T_NAMESPACE) {
    //                     for ($j=$i+1;$j<count($tokens); $j++) {
    //                         if ($tokens[$j][0] === T_STRING) {
    //                             $namespace .= '\\'.$tokens[$j][1];
    //                         } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
    //                             break;
    //                         }
    //                     }
    //                 }
    //                 if ($tokens[$i][0] === T_CLASS) {
    //                     for ($j=$i+1;$j<count($tokens);$j++) {
    //                         if ($tokens[$j] === '{') {
    //                             $class = $tokens[$i+2][1];
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //     }
    //     return $class;
    // }

}