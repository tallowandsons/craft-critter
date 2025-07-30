<?php

namespace tallowandsons\critter\generators;

use Craft;
use craft\helpers\App;
use craft\helpers\Console;
use tallowandsons\critter\Critter;
use tallowandsons\critter\models\CssModel;
use tallowandsons\critter\models\GeneratorResponse;
use tallowandsons\critter\models\UrlModel;
use Symfony\Component\Process\Process;

/**
 * Critical CSS CLI Generator
 *
 * Note: This generator is not registered by default for security reasons.
 * It requires Node.js and @plone/critical-css-cli to be installed on the server,
 * which can pose security risks if not configured properly.
 *
 * To enable this generator, add it to the generators list in your config/critter.php file:
 *
 * return [
 *     'generators' => [
 *         \tallowandsons\critter\generators\NoGenerator::class,
 *         \tallowandsons\critter\generators\CriticalCssDotComGenerator::class,
 *         \tallowandsons\critter\generators\CriticalCssCliGenerator::class, // Add this line
 *     ],
 *     'generatorType' => \tallowandsons\critter\generators\CriticalCssCliGenerator::class,
 *     'generatorSettings' => [
 *         'nodeExecutable' => '/usr/bin/node',
 *         'packageExecutable' => 'node_modules/@plone/critical-css-cli',
 *         'width' => 1300,
 *         'height' => 900,
 *         'timeout' => 60,
 *     ],
 * ];
 */
class CriticalCssCliGenerator extends BaseGenerator
{
    public string $handle = 'critical-css-cli';

    /**
     * @var string Node.js executable path
     */
    public string $nodeExecutable = 'node';

    /**
     * @var string Package executable path
     */
    public string $packageExecutable = 'node_modules/@plone/critical-css-cli';

    /**
     * @var int Timeout in seconds
     */
    public int $timeout = 60;

    /**
     * @var int Viewport width
     */
    public int $width = 1300;

    /**
     * @var int Viewport height
     */
    public int $height = 900;

    public function __construct()
    {
        $generatorSettings = Critter::getInstance()->settings->generatorSettings ?? [];

        // Load settings from configuration
        $this->setAttributes($generatorSettings, false);

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['nodeExecutable', 'packageExecutable'], 'string'],
            [['nodeExecutable', 'packageExecutable'], 'required'],
            [['nodeExecutable'], 'validateNodeExecutable'],
            [['packageExecutable'], 'validatePackageExecutable'],
            [['timeout', 'width', 'height'], 'integer', 'min' => 1],
            [['timeout'], 'integer', 'min' => 10, 'max' => 300],
            [['width'], 'integer', 'min' => 320, 'max' => 3840],
            [['height'], 'integer', 'min' => 240, 'max' => 2160],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'nodeExecutable' => Critter::translate('Node.js Executable Path'),
            'packageExecutable' => Critter::translate('Package Executable Path'),
            'timeout' => Critter::translate('Timeout'),
            'width' => Critter::translate('Viewport Width'),
            'height' => Critter::translate('Viewport Height'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Critter::translate('@plone/critical-css-cli Generator (Advanced)');
    }

    /**
     * Return the settings for this generator.
     * This is used to display the settings in the CP.
     */
    public function getSettings(): array
    {
        // Run validation to populate warnings/errors for display
        $this->validate();

        return [
            'generator' => $this,
            'settings' => Critter::getInstance()->getSettings(),
            'config' => Craft::$app->getConfig()->getConfigFromFile(Critter::getPluginHandle()),
            'pluginHandle' => Critter::getPluginHandle(),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getCriticalCss(UrlModel $urlModel): GeneratorResponse
    {
        // Check if @plone/critical-css-cli is installed before proceeding
        $packageCheck = $this->validateRequiredPackage();
        if (!$packageCheck['success']) {
            return (new GeneratorResponse())
                ->setSuccess(false)
                ->setException(new \Exception($packageCheck['message']));
        }

        $url = $urlModel->getAbsoluteUrl();
        $key = $this->getFilenameFromUrl($url);

        $outputPath = Craft::getAlias('@storage/runtime/temp/critical');
        $outputName = "$key.css";
        $output = $outputPath . '/' . $outputName;

        // Use the generator's own properties for command construction
        $command = [
            $this->getParsedNodeExecutable(),
            $this->getParsedPackageExecutable(),
            '-o',
            $output,
            '--dimensions',
            "{$this->width}x{$this->height}",
            $url
        ];

        $process = new Process($command);
        $process->setTimeout($this->timeout);
        $process->setWorkingDirectory(CRAFT_BASE_PATH);

        try {
            $process->run();

            Console::stdout($process->getOutput() . PHP_EOL, Console::FG_GREEN);
            Console::stdout($process->getErrorOutput() . PHP_EOL, Console::FG_RED);

            if ($process->isSuccessful() && is_readable($output)) {
                $cssStr = file_get_contents($output);

                return (new GeneratorResponse())
                    ->setSuccess(true)
                    ->setCss(new CssModel($cssStr));
            } else {
                return (new GeneratorResponse())
                    ->setSuccess(false)
                    ->setException(new \Exception($process->getErrorOutput()));
            }
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            return (new GeneratorResponse())
                ->setSuccess(false)
                ->setException(new \Exception("Critical CSS generation timed out after {$this->timeout} seconds. Consider increasing the timeout setting."));
        } catch (\Exception $e) {
            return (new GeneratorResponse())
                ->setSuccess(false)
                ->setException($e);
        }
    }

    /**
     * Check if @plone/critical-css-cli is installed and accessible
     * This method is designed to work in both web and CLI contexts
     */
    private function validateRequiredPackage(): array
    {
        $nodeExecutable = $this->getParsedNodeExecutable();
        $packageExecutable = $this->getParsedPackageExecutable();
        $workingDirectory = CRAFT_BASE_PATH;

        // Test if Node.js is available
        $nodeCheck = new Process([$nodeExecutable, '--version']);
        $nodeCheck->setWorkingDirectory($workingDirectory);

        // Set a reasonable timeout for validation
        $nodeCheck->setTimeout(10);

        try {
            $nodeCheck->run();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Failed to execute Node.js: {$e->getMessage()}. Check that Node.js is installed and accessible."
            ];
        }

        if (!$nodeCheck->isSuccessful()) {
            $errorDetails = $nodeCheck->getErrorOutput() ?: 'No error output available';
            return [
                'success' => false,
                'message' => "Node.js executable not found at '$nodeExecutable'. Error: $errorDetails\n\nPlease check the Node.js Executable Path setting or install Node.js."
            ];
        }

        // Verify Node.js version output looks legitimate
        $nodeVersion = trim($nodeCheck->getOutput());
        if (!preg_match('/^v\d+\.\d+\.\d+/', $nodeVersion)) {
            return [
                'success' => false,
                'message' => "Invalid Node.js executable - unexpected version output: '$nodeVersion'"
            ];
        }

        // Test if the critical-css-cli package is available
        $testCommand = [$nodeExecutable, $packageExecutable, '--help'];
        $testProcess = new Process($testCommand);
        $testProcess->setWorkingDirectory($workingDirectory);
        $testProcess->setTimeout(10);

        try {
            $testProcess->run();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Failed to execute @plone/critical-css-cli: {$e->getMessage()}\n\nCheck that the package is installed with 'npm install @plone/critical-css-cli'."
            ];
        }

        if (!$testProcess->isSuccessful()) {
            $errorOutput = $testProcess->getErrorOutput() ?: 'No error output available';
            $context = Craft::$app->getRequest()->getIsConsoleRequest() ? 'CLI' : 'Web';
            return [
                'success' => false,
                'message' => "Unable to execute '@plone/critical-css-cli' at '$packageExecutable' (Context: $context). Error: $errorOutput\n\nPlease check the Package Executable Path setting or install the package with 'npm install @plone/critical-css-cli'."
            ];
        }

        // Verify the help output contains expected content
        $helpOutput = $testProcess->getOutput();
        if (!str_contains($helpOutput, 'critical') && !str_contains($helpOutput, 'CSS')) {
            return [
                'success' => false,
                'message' => "The executable at '$packageExecutable' does not appear to be the @plone/critical-css-cli package."
            ];
        }

        return [
            'success' => true,
            'nodeExecutable' => $nodeExecutable,
            'packageExecutable' => $packageExecutable
        ];
    }

    /**
     * Get the parsed Node.js executable path with environment variable support
     */
    public function getParsedNodeExecutable(): string
    {
        return App::parseEnv($this->nodeExecutable);
    }

    /**
     * Get the parsed package executable path with environment variable support
     */
    public function getParsedPackageExecutable(): string
    {
        return App::parseEnv($this->packageExecutable);
    }

    /**
     * Validate Node.js executable path
     */
    public function validateNodeExecutable($attribute, $params): void
    {
        $path = $this->getParsedNodeExecutable();

        // Security checks - these BLOCK saving
        if ($this->isDangerousPath($path)) {
            $this->addError($attribute, 'Invalid Node.js executable path: contains dangerous patterns.');
            return;
        }

        // Functionality checks - these just warn but don't block saving
        if (!$this->isExecutableAccessible($path)) {
            $this->addWarning($attribute, 'Node.js executable not found or not accessible at: ' . $path);
            Critter::warning("Node.js executable validation failed: Node.js not found at '$path'");
        }
    }

    /**
     * Validate package executable path
     */
    public function validatePackageExecutable($attribute, $params): void
    {
        $path = $this->getParsedPackageExecutable();

        // Security checks - these BLOCK saving
        if ($this->isDangerousPath($path)) {
            $this->addError($attribute, 'Invalid package executable path: contains dangerous patterns.');
            return;
        }

        // Functionality checks - these just warn but don't block saving
        // Use the comprehensive package validation that actually tests execution
        $packageCheck = $this->validateRequiredPackage();
        if (!$packageCheck['success']) {
            $this->addWarning($attribute, 'Package executable not found at: ' . $path . '. Check the Critter logs for more details.');
            Critter::warning("Package executable validation failed: {$packageCheck['message']}");
        }
    }

    /**
     * Check if a path contains dangerous patterns
     */
    private function isDangerousPath(string $path): bool
    {
        $dangerousPatterns = [
            '../',
            '~/',
            '/etc/',
            '/usr/bin/rm',
            '/usr/bin/sudo',
            '/bin/rm',
            '/bin/sudo',
            'rm ',
            'sudo ',
            'chmod ',
            'chown ',
            '; ',
            '&& ',
            '|| ',
            '| ',
            '$()',
            '`',
            '$(',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an executable is accessible
     */
    private function isExecutableAccessible(string $path): bool
    {
        // For environment variables or commands without full paths, try to find them
        if (!str_contains($path, '/')) {
            // This is likely a command name, try to find it with 'which'
            $process = new \Symfony\Component\Process\Process(['which', $path]);
            $process->run();
            return $process->isSuccessful();
        }

        // For full paths, check if the file exists and is executable
        return file_exists($path) && is_executable($path);
    }

    private function getFilenameFromUrl(string $url): string
    {
        return md5($url);
    }
}
