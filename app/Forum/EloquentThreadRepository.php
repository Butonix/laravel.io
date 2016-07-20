<?php

namespace Lio\Forum;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Lio\Forum\Topics\Topic;
use Lio\Users\User;

final class EloquentThreadRepository implements ThreadRepository
{
    /**
     * @var \Lio\Forum\EloquentThread
     */
    private $model;

    public function __construct(EloquentThread $model)
    {
        $this->model = $model;
    }

    /**
     * @return \Lio\Forum\Thread[]|\Illuminate\Contracts\Pagination\Paginator
     */
    public function findAllPaginated()
    {
        return $this->model->orderBy('created_at', 'desc')->paginate(20);
    }

    public function find(int $id): Thread
    {
        return $this->model->findOrFail($id);
    }

    public function findBySlug(string $slug): Thread
    {
        return $this->model->where('slug', $slug)->firstOrFail();
    }

    public function create(User $author, Topic $topic, string $subject, string $body, array $attributes = []): Thread
    {
        $thread = $this->model->newInstance(compact('subject', 'body'));
        $thread->authorRelation()->associate($author);
        $thread->topicRelation()->associate($topic);

        // Todo: Figure out what to do with these
        $thread->slug = Str::slug($subject);

        $thread->save();

        $this->updateTags($thread, $attributes);

        return $thread;
    }

    public function update(Thread $thread, array $attributes = []): Thread
    {
        $thread->update(Arr::only($attributes, ['subject', 'body']));

        $thread = $this->updateTopic($thread, $attributes);
        $thread = $this->updateTags($thread, $attributes);
        $thread->save();

        return $thread;
    }

    private function updateTopic(EloquentThread $thread, $attributes): EloquentThread
    {
        if ($topic = Arr::get($attributes, 'topic')) {
            $thread->topicRelation()->associate($topic);
        }

        return $thread;
    }

    private function updateTags(EloquentThread $thread, array $attributes): EloquentThread
    {
        if ($tags = Arr::get($attributes, 'tags')) {
            $thread->tagsRelation()->sync($attributes['tags']);
        }

        return $thread;
    }

    public function delete(Thread $thread)
    {
        $thread->delete();
    }
}
