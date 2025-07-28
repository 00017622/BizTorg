<?php

class Person {
    public string $name;
    private int $age;
    protected string $passportId;

    public function __construct(string $name,int $age,string $passportId) {
        $this->name = $name;
        $this->age = $age;
        $this->passportId = $passportId;
    }

    // we can also define return types in functions

    public function getName(): string {
        return $this->name;
    }

    public function getInfo () {
        echo 'The persons name is: ' . $this->name . ' whose age is: ' . $this->age . ' and whose passportId is: ' . $this->passportId;
    }
}

class Student extends Person {

    public function getStudentName() {
echo 'This students name is: ' . $this->name;
    }
    
}




$person = new Person('Bogdan', 19, 'AD1118296');

$person->passpor
