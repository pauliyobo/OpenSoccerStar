<?php

namespace OSS\CoreBundle\Tests\Entity;

use OSS\CoreBundle\Entity\Event;
use OSS\CoreBundle\Entity\Fixture;
use OSS\CoreBundle\Entity\Player;
use OSS\CoreBundle\Entity\Team;
use OSS\CoreBundle\Services\MatchEvaluationService;

class FixtureTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \OSS\CoreBundle\Exception\MatchException
     */
    public function testSetTeam()
    {
        $fixture = new Fixture();
        $team = new Team();

        $fixture->setTeamHome($team);
        $fixture->setTeamAway($team);
    }

    public function testScoreMatchesEvents()
    {
        $fixture = new Fixture();
        $team1 = new Team();
        $team1->setId(1);
        $team2 = new Team();
        $team2->setId(2);
        $fixture->setTeamHome($team1);
        $fixture->setTeamAway($team2);

        $this->assertEquals(0, $this->countGoalsInEvents($fixture, $fixture->getTeamHome()));
        $this->assertEquals(0, $this->countGoalsInEvents($fixture, $fixture->getTeamAway()));
        $this->assertEquals(0, $fixture->getGoalsScored());

        $fixture->addEvent(Event::createGoal($fixture, $fixture->getTeamHome(), new Player(), 1));
        $this->assertEquals(1, $this->countGoalsInEvents($fixture, $fixture->getTeamHome()));
        $this->assertEquals(0, $this->countGoalsInEvents($fixture, $fixture->getTeamAway()));
        $this->assertEquals(1, $fixture->getScoreHome());
        $this->assertEquals(0, $fixture->getScoreAway());
        $this->assertEquals(1, $fixture->getGoalsScored());
    }

    public function testNoScoreBeforeMatchStart()
    {
        $fixture = new Fixture();
        $this->assertNull($fixture->getScoreHome());
        $this->assertNull($fixture->getScoreAway());
    }

    public function testZeroScoreAfterMatchStart()
    {
        $fixture = new Fixture();
        $team1 = new Team();
        $team1->setId(1);
        $team1->addPlayer(new Player());
        $team2 = new Team();
        $team2->setId(2);
        $team2->addPlayer(new Player());
        $fixture->setTeamHome($team1);
        $fixture->setTeamAway($team2);

        $matchEvaluationService = new MatchEvaluationService();
        $matchEvaluationService->evaluateMinuteOfMatch($fixture);
        $this->assertNotNull($fixture->getScoreHome());
        $this->assertNotNull($fixture->getScoreAway());
    }

    /**
     * @expectedException \OSS\CoreBundle\Exception\MatchException
     */
    public function testNoWinnerException()
    {
        $fixture = new Fixture();
        $fixture->resetScoreHome();
        $fixture->resetScoreAway();

        $fixture->getWinner();
    }

    public function testDraw()
    {
        $fixture = new Fixture();
        $fixture->resetScoreHome();
        $fixture->resetScoreAway();

        $this->assertTrue($fixture->isDraw());
    }

    public function testGetGoalEvents()
    {
        $team1 = new Team();
        $team1->setId(1);
        $team2 = new Team();
        $team2->setId(2);

        $fixture = new Fixture();
        $fixture->setTeamHome($team1);
        $fixture->setTeamAway($team2);
        $fixture->addEvent(Event::createGoal($fixture, $team1, new Player(), 1));
        $fixture->addEvent(Event::createChance($fixture, $team2, new Player(), 2));

        $this->assertCount(1, $fixture->getGoalEvents());
    }

    /**
     * @param Fixture $fixture
     * @param Team $team
     *
     * @return int
     */
    private function countGoalsInEvents(Fixture $fixture, Team $team)
    {
        $goalsInEvents = 0;
        foreach ($fixture->getEvents() as $event) {
            if ($event->isGoal() && $event->getTeam()->equals($team)) {
                $goalsInEvents++;
            }
        }

        return $goalsInEvents;
    }
}
