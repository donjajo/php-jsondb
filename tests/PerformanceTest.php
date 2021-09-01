<?php
declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;
use \Jajo\JSONDB;

class PerformanceTest extends TestCase {
	private $jsondb;

	protected function setUp(): void {
		$this->jsondb = new JSONDB(__DIR__, JSON_UNESCAPED_UNICODE);
	}

	protected function tearDown(): void {
		unlink( __DIR__ . '/food.json' );
	}

	public function testInsert(): void {
		$i = 0;
		while ( $i < 5000 ) {
			$sum = 0;
			for ($j = 0; $j < 1000; $j++ ) {
				$start = hrtime( true );
				$this->jsondb->insert( 'food', array(
					'name' => 'Rice',
					'class' => 'Carbohydrate',
				) );
				$stop = hrtime( true );
				$sum += ( $stop - $start )/1000000;
			}
			$i += $j;
			fprintf( STDOUT, "\nTook average of %fms to insert 1000 records - BATCH %d", $sum, $i / 1000 );
			fflush(STDOUT);
		}

		$foods = $this->jsondb->select('name')->from( 'food' )->get();
		$this->assertCount( 5000, $foods );
	}
}