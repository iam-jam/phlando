<?php
namespace Console\App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ServiceCommand extends Command
{

  /**
   * The path to the Lando file.
   *
   * @var string
   */
  protected $landoFilePath = '';

  /**
   * The parsed Lando config.
   *
   * @var array
   */
  protected $landoConfig = [];

  /**
   * @var array
   */
  protected $services = [
    'mailhog',
    'php',
    'apache'
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct($name = NULL) {
    parent::__construct($name);
    $this->landoFilePath = getcwd() . '/.lando.yml';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this->setName('scaffold')
      ->setDescription('Scaffold a Lando service.');
  }

  /**
   * Set the existing Lando config.
   */
  function setLandoConfig() {
    if (!file_exists($this->landoFilePath)) {
      throw new RuntimeException(sprintf('No Lando file found at %s', getcwd()));
    }
    $data = file_get_contents($this->landoFilePath);
    try {
      $this->landoConfig = Yaml::parse($data);
    }
    catch (\Exception $e) {
      throw new RuntimeException('Unable to parse the Lando file.');
    }
  }

  /**
   * Scaffold a Lando service.
   *
   * @param $service string
   *   The name of the Lando service to scaffold.
   * @param $key string
   *   The key of the Lando service to scaffold.
   *
   */
  protected function addService($service, $key) {
    if (!isset($this->landoConfig['services'])) {
      $this->landoConfig['services'] = [];
    }
    $this->landoConfig['services'][$key] = [
      'type' => $service
    ];
  }

  /**
   * Write the Lando config to the filesystem.
   */
  protected function writeLandoConfig() {
    $yaml = Yaml::dump($this->landoConfig);
    file_put_contents($this->landoFilePath, $yaml);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setLandoConfig();
    $helper = $this->getHelper('question');
    $serviceQuestion = new ChoiceQuestion(
      'Please choose a Lando service to add.',
      $this->services,
      0
    );
    $serviceQuestion->setErrorMessage('%s is invalid.');
    $service = $helper->ask($input, $output, $serviceQuestion);

    $keyQuestion = new Question('What key do you want to use?');
    $key = $helper->ask($input, $output, $keyQuestion);

    $this->addService($service, $key);
    $this->writeLandoConfig();
    $output->writeln(sprintf('The %s service has been scaffoled in your Lando file.', $service));
  }
}
