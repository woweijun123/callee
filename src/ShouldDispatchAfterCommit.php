<?php

namespace Callee;

/**
 * 标记接口，实现此接口的消息将在数据库事务提交后发送
 */
interface ShouldDispatchAfterCommit
{
}
