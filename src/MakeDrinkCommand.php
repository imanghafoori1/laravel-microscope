<?php

namespace GetWith\CoffeeMachine\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeDrinkCommand extends Command
{
    protected static $defaultName = 'app:order-drink';

    private static $prices = [
        'tea' => 0.4,
        'coffee' => 0.5,
        'chocolate' => 0.6,
    ];

    public function __construct()
    {
        parent::__construct(MakeDrinkCommand::$defaultName);
    }

    protected function configure()
    {
        $this->addArgument(
            'drink-type',
            InputArgument::REQUIRED,
            'The type of the drink. (Tea, Coffee or Chocolate)'
        );

        $this->addArgument(
            'money',
            InputArgument::REQUIRED,
            'The amount of money given by the user'
        );

        $this->addArgument(
            'sugars',
            InputArgument::OPTIONAL,
            'The number of sugars you want. (0, 1, 2)',
            0
        );

        $this->addOption(
            'extra-hot',
            'e',
            InputOption::VALUE_NONE,
            $description = 'If the user wants to make the drink extra hot'
        );
    }

    private function validationError($msg)
    {
        throw new \Exception($msg, 100);
    }

    private function orderDrink($input, $output)
    {
        $drinkType = $input->getArgument('drink-type');
        $sugars = $input->getArgument('sugars');
        $extraHot = $input->getOption('extra-hot');
        $money = $input->getArgument('money');

        $drinkType = strtolower($drinkType);
        $this->validateDrinkType($drinkType);
        $this->validateHasEnoughMoney($money, $drinkType);
        $this->validateSugar($sugars);
        $this->printMassages($this->getOrderMsg($extraHot, $drinkType, $sugars), $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->orderDrink($input, $output);
        } catch (\Exception $e) {
            if ($e->getCode() !== 100) {
                throw $e;
            }
            $this->handleValidationErrors($output, $e->getMessage());
        }

        return 0;
    }

    protected function getOrderMsg($extraHot, $drinkType, $sugars)
    {
        $msg = [];

        $msg[] = 'You have ordered a '.$drinkType;

        $extraHot && $msg[] = ' extra hot';

        $msg[] = ' with '.$sugars.' sugars (stick included)';

        return $msg;
    }

    protected function validateSugar($sugars)
    {
        if ($sugars < 0 || $sugars > 2) {
            $this->validationError('The number of sugars should be between 0 and 2.');
        }
    }

    protected function validateHasEnoughMoney($money, string $drinkType)
    {
        $price = self::$prices[$drinkType];

        if ($money < $price) {
            $this->validationError('The '.$drinkType.' costs '.$price.'.');
        }
    }

    protected function validateDrinkType(string $drinkType)
    {
        if (! in_array($drinkType, ['tea', 'coffee', 'chocolate'])) {
            $this->validationError('The drink type should be "tea", "coffee" or "chocolate".');
        }
    }

    protected function printMassages($msg, $output)
    {
        foreach ($msg as $text) {
            $output->writeln($text);
        }
        $output->writeln('');
    }

    protected function handleValidationErrors($output, $msg)
    {
        $output->writeln($msg);
    }
}
