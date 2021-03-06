<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\Doctrine\NullObjectManager;
use Algolia\SearchBundle\Entity\Comment;
use Algolia\SearchBundle\Entity\Post;
use Algolia\SearchBundle\Entity\Tag;

class IndexManagerTest extends BaseTest
{
    /** @var \Algolia\SearchBundle\IndexManagerInterface */
    protected $indexManager;

    public function setUp()
    {
        parent::setUp();
        $this->indexManager = $this->get('search.index_manager');
    }

    public function tearDown()
    {
        $this->indexManager->delete(Post::class);
        $this->indexManager->delete(Comment::class);
    }
    public function testIsSearchableMethod()
    {
        $this->assertTrue($this->indexManager->isSearchable(Post::class));
        $this->assertTrue($this->indexManager->isSearchable(Comment::class));

        $this->assertFalse($this->indexManager->isSearchable(BaseTest::class));
    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionIfNoId()
    {
        $om = $this->get('doctrine')->getManager();

        $this->indexManager->index(new Post, $om);
    }

    public function testIndexedDataAreSearchable()
    {
        $om = $this->get('doctrine')->getManager();

        $posts = [];
        for ($i=0; $i<3; $i++) {
            $posts[] = $this->createPost($i);
        }

        // index Data
        $this->indexManager->index($this->createPost(10), $om);
        $this->indexManager->index(array_merge($posts, [$this->createComment(1)]), $om);

        // RawSearch
        $searchPost = $this->indexManager->rawSearch('', Post::class);
        $this->assertCount(4, $searchPost['hits']);
        $searchPost = $this->indexManager->rawSearch('', Post::class, 1, 1);
        $this->assertCount(1, $searchPost['hits']);

        $searchPostEmpty = $this->indexManager->rawSearch('with no result', Post::class);
        $this->assertCount(0, $searchPostEmpty['hits']);

        $searchComment = $this->indexManager->rawSearch('', Comment::class);
        $this->assertCount(1, $searchComment['hits']);

        // Count
        $this->assertEquals(4, $this->indexManager->count('test', Post::class));
        $this->assertEquals(1, $this->indexManager->count('content', Comment::class));

        // Cleanup
        $this->indexManager->delete(Post::class);
        $this->indexManager->delete(Comment::class);
    }
}
